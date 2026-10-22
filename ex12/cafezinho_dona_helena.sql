
create database cafezinho_dona_helena
    character set utf8mb4
    collate utf8mb4_unicode_ci;
use cafezinho_dona_helena;

create table usuarios (
    id int primary key auto_increment,
    nome varchar(100) not null,
    email varchar(100) not null unique,
    senha varchar(255) not null,
    data_nascimento date not null,
    genero enum('Masculino', 'Feminino', 'Outro') not null,
    novela_favorita varchar(100) not null,
    interesses json not null,
    bio text not null
);

create table resultado_quiz(
    id int primary key auto_increment,
    id_usuario int null,
    resultado varchar(100) not null,
    comentario text,
    constraint fk_resultado_quiz_usuario
        foreign key (id_usuario) references usuarios(id)
        on delete set null
);