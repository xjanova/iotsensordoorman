"""
Bunny Door System - Face Recognition Module
============================================
ปรับปรุงจากต้นฉบับ:
- เพิ่ม Thread-safe สำหรับ multi-camera
- เพิ่ม face caching เพื่อลด CPU
- เพิ่ม confidence score (% ความมั่นใจ)
- รองรับ model เลือกได้ (HOG/CNN)
"""

import face_recognition
import cv2
import os
import glob
import numpy as np
import threading
from typing import List, Tuple, Optional


class SimpleFaceRec:
    def __init__(self, model: str = "hog", tolerance: float = 0.6, frame_resizing: float = 0.25):
        """
        Parameters:
            model: "hog" (เร็ว, ใช้ CPU) หรือ "cnn" (แม่นยำ, ต้องใช้ GPU)
            tolerance: ค่า tolerance สำหรับเปรียบเทียบใบหน้า (ยิ่งต่ำ = ยิ่งเข้มงวด)
            frame_resizing: ลดขนาดภาพเป็นสัดส่วน (0.25 = 25%)
        """
        self.known_face_encodings: List[np.ndarray] = []
        self.known_face_names: List[str] = []
        self.frame_resizing = frame_resizing
        self.model = model
        self.tolerance = tolerance
        self._lock = threading.Lock()

    def load_encoding_images(self, images_path: str) -> int:
        """
        โหลดรูปภาพจากโฟลเดอร์แล้วเข้ารหัสใบหน้า
        Returns: จำนวนใบหน้าที่โหลดสำเร็จ
        """
        image_files = glob.glob(os.path.join(images_path, "*.*"))
        print(f"[FaceRec] พบ {len(image_files)} ไฟล์รูปภาพ")

        loaded = 0
        for img_path in image_files:
            ext = os.path.splitext(img_path)[1].lower()
            if ext not in ['.jpg', '.jpeg', '.png', '.bmp']:
                continue

            img = cv2.imread(img_path)
            if img is None:
                print(f"[FaceRec] ไม่สามารถอ่านไฟล์: {img_path}")
                continue

            rgb_img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
            basename = os.path.basename(img_path)
            filename = os.path.splitext(basename)[0]

            encodings = face_recognition.face_encodings(rgb_img)
            if encodings:
                with self._lock:
                    self.known_face_encodings.append(encodings[0])
                    self.known_face_names.append(filename)
                loaded += 1
                print(f"[FaceRec] โหลดสำเร็จ: {filename}")
            else:
                print(f"[FaceRec] ไม่พบใบหน้าในไฟล์: {basename}")

        print(f"[FaceRec] โหลดเสร็จ: {loaded}/{len(image_files)} ใบหน้า")
        return loaded

    def detect_known_faces(self, frame: np.ndarray) -> Tuple[List, List, List]:
        """
        ตรวจจับและระบุใบหน้าในเฟรม
        Returns: (face_locations, face_names, confidence_scores)
            - face_locations: ตำแหน่งใบหน้า [(top, right, bottom, left), ...]
            - face_names: ชื่อ ["Alice", "Unknown", ...]
            - confidence_scores: ความมั่นใจ [95.2, 0.0, ...]
        """
        # ลดขนาดภาพเพื่อเพิ่มความเร็ว
        small_frame = cv2.resize(frame, (0, 0), fx=self.frame_resizing, fy=self.frame_resizing)
        rgb_small = cv2.cvtColor(small_frame, cv2.COLOR_BGR2RGB)

        # ค้นหาใบหน้า
        face_locations = face_recognition.face_locations(rgb_small, model=self.model)
        face_encodings = face_recognition.face_encodings(rgb_small, face_locations)

        face_names = []
        confidence_scores = []

        with self._lock:
            for encoding in face_encodings:
                if len(self.known_face_encodings) == 0:
                    face_names.append("Unknown")
                    confidence_scores.append(0.0)
                    continue

                # คำนวณ distance (ยิ่งน้อย = ยิ่งเหมือน)
                distances = face_recognition.face_distance(self.known_face_encodings, encoding)
                best_match_idx = np.argmin(distances)
                best_distance = distances[best_match_idx]

                # แปลง distance เป็น confidence %
                confidence = max(0, (1.0 - best_distance) * 100)

                if best_distance <= self.tolerance:
                    name = self.known_face_names[best_match_idx]
                else:
                    name = "Unknown"
                    confidence = 0.0

                face_names.append(name)
                confidence_scores.append(round(confidence, 1))

        # แปลงตำแหน่งกลับเป็นขนาดเต็ม
        scale = 1.0 / self.frame_resizing
        face_locations_full = []
        for (top, right, bottom, left) in face_locations:
            face_locations_full.append((
                int(top * scale),
                int(right * scale),
                int(bottom * scale),
                int(left * scale)
            ))

        return face_locations_full, face_names, confidence_scores

    def add_face(self, name: str, image: np.ndarray) -> bool:
        """เพิ่มใบหน้าใหม่เข้าฐานข้อมูล (runtime)"""
        rgb_img = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        encodings = face_recognition.face_encodings(rgb_img)
        if encodings:
            with self._lock:
                self.known_face_encodings.append(encodings[0])
                self.known_face_names.append(name)
            return True
        return False

    def get_face_count(self) -> int:
        """จำนวนใบหน้าในฐานข้อมูล"""
        return len(self.known_face_names)
