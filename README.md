# Rive

Rive é um projeto Laravel que tem como objetivo ajudar no armazenamento de arquivos.

## Instalação

- Instalar [Docker Desktop](https://www.docker.com/products/docker-desktop/);
- Instalar [WSL e Ubuntu](https://balta.io/blog/wsl);

## Download do projeto
Clone o projeto a partir do repositório
```bash
git clone https://github.com/casserekevin/rive.git
```

## Uso

Com o VScode aberto em modo WSL, executar os seguintes comandos no terminal:
- Carregar o arquivo **.bashrc**
```bash
source ~/.bashrc
```
- Subir o projeto com docker
```bash
sail up -d
```
- Entrar no terminal(bash) do projeto dentro do docker
```bash
sail bash
```
- Pelo bash do projeto, executar as migrations que vão criar os bancos de dados vazios
```bash
php artisan migrate
```
- Executar o projeto em modo desenvolvedor
```bash
npm run dev
```


## Licença

[MIT](https://choosealicense.com/licenses/mit/)
