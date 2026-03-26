import express from "express";

const app = express();
const port = 3000;

app.get("/", (_req, res) => {
  res.json({
    message: "Lab Node.js com Docker Multi-Stage",
    status: "ok"
  });
});

app.listen(port, () => {
  console.log(`Servidor rodando na porta ${port}`);
});