import time
import os
from pdf2image import convert_from_path
from pdfminer.high_level import extract_text
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler


def crop_pdf_area(input_path, output_path, x, y, width, height):
    images = convert_from_path(input_path)
    cropped = images[0].crop((x, y, x + width, y + height))
    cropped.save(output_path, 'PDF')


def extract_text_from_pdf(file_path):
    return extract_text(file_path).strip()


def process_pdf(folder_path):
    extracted_texts = {}
    crops = [

        {"x": 125, "y": 110, "width": 80, "height": 25, "output": "IssRetido.pdf"},
        {"x": 125, "y": 80, "width": 80, "height": 25, "output": "ValorLiquido.pdf"},
        {"x": 150, "y": 210, "width": 50, "height": 25, "output": "ValorServicos.pdf"},
        {"x": 500, "y": 210, "width": 50, "height": 25, "output": "ValorServicos2.pdf"},
        {"x": 500, "y": 150, "width": 50, "height": 25, "output": "BaseCalculo.pdf"},
        {"x": 500, "y": 130, "width": 50, "height": 25, "output": "Aliquota.pdf"},
        {"x": 500, "y": 80, "width": 50, "height": 25, "output": "ISS.pdf"},
        {"x": 128, "y": 330, "width": 80, "height": 25, "output": "CodigoServico.pdf"},
        {"x": 100, "y": 250, "width": 50, "height": 25, "output": "PIS.pdf"},
        {"x": 195, "y": 250, "width": 50, "height": 25, "output": "COFINS.pdf"},
        {"x": 290, "y": 250, "width": 50, "height": 25, "output": "IR.pdf"},
        {"x": 385, "y": 250, "width": 50, "height": 25, "output": "INSS.pdf"},
        {"x": 480, "y": 250, "width": 100, "height": 25, "output": "CSLL.pdf"},
        {"x": 70, "y": 520, "width": 100, "height": 20, "output": "CNPJ.pdf"},
        {"x": 435, "y": 727, "width": 50, "height": 25, "output": "NotaFiscal.pdf"},
        {"x": 300, "y": 700, "width": 75, "height": 25, "output": "Data.pdf"},
        {"x": 130, "y": 610, "width": 80, "height": 18, "output": "CNPJPrestador.pdf"},
        {"x": 130, "y": 610, "width": 80, "height": 18, "output": "CNPJ2.pdf"},
    ]

    for root, _, files in os.walk(folder_path):
        for file in files:
            if file.endswith('.pdf'):
                pdf_path = os.path.join(root, file)
                for crop in crops:
                    output_path = os.path.join(root, crop["output"])
                    crop_pdf_area(pdf_path, output_path, crop["x"], crop["y"], crop["width"], crop["height"])
                    text = extract_text_from_pdf(output_path)
                    extracted_texts[crop["output"].replace(".pdf", "")] = text

    return extracted_texts

class Handler(FileSystemEventHandler):
    def on_created(self, event):
        print(f"Evento detectado: {event.src_path}")  # Esta linha irá mostrar todos os eventos

        if event.is_directory:
            print(f"Novo diretório detectado: {event.src_path}")
            all_extracted_texts = []
            for root, dirs, _ in os.walk(event.src_path):
                for dir in dirs:
                    child_path = os.path.join(root, dir)
                    extracted_texts = process_pdf(child_path)
                    all_extracted_texts.append(','.join([val for _, val in extracted_texts.items()]))

            # Escrevendo todas as variáveis extraídas no arquivo .txt
            with open(os.path.join(event.src_path, 'extracted_texts.txt'), 'w') as f:
                for line in all_extracted_texts:
                    f.write(line + '\n')

if __name__ == "__main__":
    path_to_watch = r'C:\Empenho - Copia\public\writable\uploads\pdf'  # Atualize para o caminho da pasta que você quer observar
    observer = Observer()
    event_handler = Handler()
    observer.schedule(event_handler, path_to_watch, recursive=False)  # False pois estamos observando apenas a criação de novas pastas no diretório principal
    observer.start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
