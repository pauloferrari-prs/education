USE source_db;

CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente VARCHAR(100),
  produto VARCHAR(100),
  valor DECIMAL(10,2),
  data_pedido DATE
);

INSERT INTO pedidos (cliente, produto, valor, data_pedido) VALUES
('Ana', 'Notebook', 3500.00, '2026-04-01'),
('Bruno', 'Mouse', 120.00, '2026-04-02'),
('Carla', 'Teclado', 280.00, '2026-04-03'),
('Diego', 'Monitor', 999.90, '2026-04-04');