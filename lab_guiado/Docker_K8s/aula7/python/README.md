### Local
```bash
python3 -m venv .venv

source .venv/bin/activate

python -m pip install --upgrade pip

python -m pip install -r requirements.txt

python app.py

curl http://localhost:5000

deactivate
```

### Single Stage
```bash
docker build -f Dockerfile.single -t python-lab:single .

docker run --rm -d --name py-single -p 5001:5000 python-lab:single

curl http://localhost:5001
```


### Multi Stage
```bash
docker build -f Dockerfile.multi -t python-lab:multi .

docker run --rm -d --name py-multi -p 5002:5000 python-lab:multi

curl http://localhost:5002
```

### Comparando

`docker history python-lab:single`

`docker history python-lab:multi`

`docker stop $(docker ps -q)`