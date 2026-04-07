import sys
import os
from pathlib import Path
from PyQt6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QHBoxLayout,
    QLabel, QPushButton, QMessageBox, QFrame
)
from PyQt6.QtCore import Qt, QMimeData
from PyQt6.QtGui import QFont, QDragEnterEvent, QDropEvent
from pypdf import PdfReader, PdfWriter


STYLESHEET = """
QWidget {
    background-color: #0b1e35;
    color: #dff0f8;
    font-family: 'Segoe UI', Arial, sans-serif;
}

QPushButton {
    background: linear-gradient(135deg, #00b8a9, #00a896);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    font-weight: 600;
    padding: 12px 24px;
    min-width: 200px;
}

QPushButton:hover {
    background: linear-gradient(135deg, #00c9b8, #00b8a9);
}

QPushButton:disabled {
    background: #1e3d63;
    color: #7aafc8;
}

QMessageBox {
    background-color: #112543;
}

QMessageBox QLabel {
    color: #dff0f8;
}
"""


class PDFSplitterApp(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("PDF Splitter")
        self.setMinimumSize(600, 400)
        self.resize(700, 500)

        self.setAcceptDrops(True)

        self.setup_ui()
        self.setStyleSheet(STYLESHEET)

    def setup_ui(self):
        layout = QVBoxLayout()
        layout.setContentsMargins(30, 30, 30, 30)
        layout.setSpacing(20)

        title = QLabel("PDF Splitter")
        title.setFont(QFont("Segoe UI", 24, QFont.Weight.Bold))
        title.setStyleSheet("color: #00c9b8;")
        title.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(title)

        subtitle = QLabel("Przeciągnij plik PDF na okno, aby podzielić na pojedyńcze strony")
        subtitle.setFont(QFont("Segoe UI", 12))
        subtitle.setStyleSheet("color: #7aafc8;")
        subtitle.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(subtitle)

        layout.addSpacing(20)

        self.drop_zone = DropZone()
        layout.addWidget(self.drop_zone)

        self.status_label = QLabel("")
        self.status_label.setFont(QFont("Segoe UI", 11))
        self.status_label.setStyleSheet("color: #7aafc8;")
        self.status_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.status_label.setWordWrap(True)
        layout.addWidget(self.status_label)

        layout.addStretch()

        self.btn_open_folder = QPushButton("Otwórz folder wyjściowy")
        self.btn_open_folder.setEnabled(False)
        self.btn_open_folder.clicked.connect(self.open_output_folder)

        btn_layout = QHBoxLayout()
        btn_layout.addStretch()
        btn_layout.addWidget(self.btn_open_folder)
        btn_layout.addStretch()
        layout.addLayout(btn_layout)

        self.setLayout(layout)

        self.output_dir = None

    def process_pdf(self, pdf_path: Path):
        try:
            self.status_label.setText("Wczytywanie PDF...")
            self.status_label.setStyleSheet("color: #ffc043;")
            QApplication.processEvents()

            reader = PdfReader(str(pdf_path))
            total_pages = len(reader.pages)

            if total_pages == 0:
                QMessageBox.warning(self, "Błąd", "PDF nie zawiera stron.")
                self.status_label.setText("")
                return

            base_name = pdf_path.stem
            output_dir = pdf_path.parent / f"SPLIT_{base_name}"
            os.makedirs(output_dir, exist_ok=True)

            self.status_label.setText(f"Przetwarzanie {total_pages} stron...")
            QApplication.processEvents()

            for i, page in enumerate(reader.pages, 1):
                writer = PdfWriter()
                writer.add_page(page)

                page_num = str(i).zfill(3)
                output_name = f"{page_num}_{base_name}.pdf"
                output_path = output_dir / output_name

                with open(output_path, "wb") as f:
                    writer.write(f)

            self.output_dir = output_dir
            self.status_label.setText(f"✓ Podzielono na {total_pages} plików w: {output_dir}")
            self.status_label.setStyleSheet("color: #26d98f;")
            self.btn_open_folder.setEnabled(True)

            QMessageBox.information(
                self,
                "Sukces",
                f"PDF został podzielony na {total_pages} plików.\n\nLokalizacja: {output_dir}"
            )

        except Exception as e:
            QMessageBox.critical(self, "Błąd", f"Nie udało się podzielić PDF:\n{str(e)}")
            self.status_label.setText("")

    def open_output_folder(self):
        if self.output_dir and os.path.isdir(self.output_dir):
            if sys.platform == "darwin":
                os.system(f'open "{self.output_dir}"')
            elif sys.platform.startswith("linux"):
                os.system(f'xdg-open "{self.output_dir}"')
            elif sys.platform == "win32":
                os.startfile(self.output_dir)


class DropZone(QFrame):
    def __init__(self):
        super().__init__()
        self.setMinimumHeight(200)
        self.setFrameStyle(QFrame.Shape.StyledPanel | QFrame.Shadow.Raised)
        self.setObjectName("dropZone")

        layout = QVBoxLayout()
        layout.setContentsMargins(20, 20, 20, 20)
        layout.setAlignment(Qt.AlignmentFlag.AlignCenter)

        icon_label = QLabel("📄")
        icon_label.setFont(QFont("Arial", 48))
        icon_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(icon_label)

        self.text_label = QLabel("Upuść plik PDF tutaj")
        self.text_label.setFont(QFont("Segoe UI", 14))
        self.text_label.setStyleSheet("color: #7aafc8;")
        self.text_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        layout.addWidget(self.text_label)

        self.setLayout(layout)
        self.setStyleSheet("""
            #dropZone {
                background-color: #112543;
                border: 2px dashed #254a76;
                border-radius: 12px;
            }
            #dropZone:hover {
                border-color: #00c9b8;
                background-color: #17304f;
            }
        """)

        self.setAcceptDrops(True)
        self.drag_over = False

    def dragEnterEvent(self, event: QDragEnterEvent):
        if event.mimeData().hasUrls():
            event.acceptProposedAction()
            self.drag_over = True
            self.update_style()

    def dragLeaveEvent(self, event):
        self.drag_over = False
        self.update_style()

    def dropEvent(self, event: QDropEvent):
        self.drag_over = False
        self.update_style()

        urls = event.mimeData().urls()
        if not urls:
            return

        file_path = Path(urls[0].toLocalFile())

        if not file_path.suffix.lower() == '.pdf':
            QMessageBox.warning(None, "Błąd", "Przeciągnięty plik nie jest PDF.")
            return

        if not file_path.exists():
            QMessageBox.warning(None, "Błąd", "Plik nie istnieje.")
            return

        window = self.window()
        window.process_pdf(file_path)

    def update_style(self):
        if self.drag_over:
            self.setStyleSheet("""
                #dropZone {
                    background-color: #17304f;
                    border: 2px dashed #00c9b8;
                    border-radius: 12px;
                }
            """)
        else:
            self.setStyleSheet("""
                #dropZone {
                    background-color: #112543;
                    border: 2px dashed #254a76;
                    border-radius: 12px;
                }
                #dropZone:hover {
                    border-color: #00c9b8;
                    background-color: #17304f;
                }
            """)


def main():
    app = QApplication(sys.argv)
    window = PDFSplitterApp()
    window.show()
    sys.exit(app.exec())


if __name__ == "__main__":
    main()
