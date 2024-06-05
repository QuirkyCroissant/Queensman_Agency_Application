FROM python:3.8-slim

WORKDIR /backend

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .

EXPOSE 5000

CMD ["python", "Queensman_imse_Insert.py"]