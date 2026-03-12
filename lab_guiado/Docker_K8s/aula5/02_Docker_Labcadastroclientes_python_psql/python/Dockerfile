#Imagem Base Python
FROM python:3.12-slim

WORKDIR /app

#Copiando dependências (libs) que serão instaladas
COPY requirements.txt /app/requirements.txt

#Instalando as libs (dependências)
RUN pip install --no-cache-dir -r requirements.txt

#Cópia do código fonrte da aplicação
COPY app.py /app/app.py

EXPOSE 5000

#Criando o container com o comando para executar a aplicação
CMD ["python", "app.py"]