import click

@click.group(context_settings={"help_option_names": ["-h", "--help"]})
def cli():
    """Calculadora simples (add/sub)."""
    pass

@cli.command()
@click.argument("a", type=float)
@click.argument("b", type=float)
def add(a: float, b: float):
    """Soma: A + B"""
    click.echo(a + b)

@cli.command()
@click.argument("a", type=float)
@click.argument("b", type=float)
def sub(a: float, b: float):
    """Subtração: A - B"""
    click.echo(a - b)

if __name__ == "__main__":
    cli()