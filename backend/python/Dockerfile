FROM python:3.8-slim

WORKDIR /backend

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .
COPY wait-for-it.sh /backend/

EXPOSE 5000

CMD ["./wait-for-it.sh", "mysqldb", "--", "python3", "Queensman_imse_Insert.py"]