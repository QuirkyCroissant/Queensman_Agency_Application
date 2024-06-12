FROM python:3.8-slim

WORKDIR /backend

COPY requirements.txt .
RUN pip install -r requirements.txt
RUN apt-get update && apt-get install -y default-mysql-client

COPY . .
COPY entrypoint.sh /backend/

RUN chmod +x /backend/entrypoint.sh

EXPOSE 5000

CMD ["/backend/entrypoint.sh"]