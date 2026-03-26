### Local
`npm install`

`npm run build`

`npm start`

### Single Stage
`docker build -f Dockerfile.single -t node-lab:single .`

`docker run --rm -d --name node-single -p 3001:3000 node-lab:single`

`curl http://localhost:3001`

### Multi Stage
`docker build -f Dockerfile.multi -t node-lab:multi .`

`docker run --rm -d --name node-multi -p 3002:3000 node-lab:multi`

`curl http://localhost:3002`

### Comparando

`docker history node-lab:single`

`docker history node-lab:multi`

`docker stop $(docker ps -q)`