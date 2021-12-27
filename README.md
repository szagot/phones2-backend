# Serviços de base para App

Serviços de base para novos Apps.

Aponte o apache para essa pasta.
Dessa forma, a base da chamada será `{uri}/phones/{servicos}`

Você pode configurar isso em `configure.php` no campo `root`. Lembre de renomear a pasta também.

---

## Mensagens de erro

Em caso de falha, será retornado um http code apropriado ao erro
(como por exemplo `401 Unauthorized` para login inválido) com o seguinte response body:

```JSON
{
    "message": "Mensagem de erro",
    "status": 400,
    "timestamp": 1633036496
}
```

---

## Serviço de login

`POST /phones/login`

Efetua o login no sistema. São esperados os seguintes campos:

Campo | Tipo | Descrição
------- | ------- | -------
`username` | `string` | Usuário
`password` | `string` | Senha. Deve ter pelo meno 6 caracteres.

Os dados do token serão enviados no header da seguinte forma:

Header | Valor
------ | -------
Content-Type | application/json
access-control-expose-headers | Authorization
authorization | Bearer xxx.yyy.zzz

E o body terá as seguintes informações:

```JSON
{
    "user": "usuario@servidor.com",
    "expiresIn": {
        "timestamp": 1632939971,
        "dateTime": "2021-09-29 18:26:11"
    }
}
```

---

## Serviços de usuário

`GET /phones/user`

Pega todos os usuários do sistema

```JSON
[
    {
        "id": "99",
        "name": "Nome do Usuário",
        "email": "email@servidor.com",
        "isAdmin": false
    }
]
```

---

`GET /phones/user/{id}`

Pega os dados do usuário de id informado

```JSON
{
    "id": "99",
    "name": "Nome do Usuário",
    "email": "email@servidor.com",
    "isAdmin": false
}
```

---

`GET /phones/myuser`

Pega os dados do usuário logado

```JSON
{
    "id": "99",
    "name": "Nome do Usuário",
    "email": "email@servidor.com",
    "isAdmin": false
}
```

---

`GET /phones/roles/me`

Pega as regras de permissão do usuário logado.

Para esse sistema, as regras aplicadas são duas: `ROLE_USER` e `ROLE_ADMIN`, sendo que para cada, ele pode ter os privilégios:
`CREATE`, `UPDATE`, `READ`, `EXECUTE` e `DELETE`.

Um usuário com acesso adinistrativo, terá o seguinte retorno:

```JSON
{
    "roles": [
        {
            "role": "ROLE_USER",
            "privileges": [
                {
                    "privilege": "CREATE"
                },
                {
                    "privilege": "UPDATE"
                },
                {
                    "privilege": "READ"
                },
                {
                    "privilege": "EXECUTE"
                },
                {
                    "privilege": "DELETE"
                }
            ]
        },
        {
            "role": "ROLE_ADMIN",
            "privileges": [
                {
                    "privilege": "CREATE"
                },
                {
                    "privilege": "UPDATE"
                },
                {
                    "privilege": "READ"
                },
                {
                    "privilege": "EXECUTE"
                },
                {
                    "privilege": "DELETE"
                }
            ]
        }
    ]
}
```

---

`DELETE /phones/user/{id}`

Apaga os dados do usuário informado.
(Não permite apagar o usuário se este for o mesmo que logou)

---

`POST /phones/user`

Cadastra um usuário. São esperados os seguintes campos

Campo | Tipo | Descrição
------- | ------- | -------
`name` | `string` | Nome do Usuário
`email` | `string` | Email do Usuário
`password` | `string` | Senha do Usuário. Deve ter pelo meno 6 caracteres.
`confirmPassword` | `string` | Confirmação de senha
`isAdmin` | `boolean` | É administrador?

Em caso de sucesso, retorno o usuário cadastrado com seu id. A senha, encriptada, jamais é informada.

```JSON
{
    "id": "99",
    "name": "Nome do Usuário",
    "email": "email@servidor.com",
    "isAdmin": false
}
```

---

`PATCH /phones/user/{id}`

Atualiza os dados de um usuário. Todos os campos são opcionais, porém ao menos um deve ser informado.
(Não é possível alterar o email):

Campo | Tipo | Descrição
------- | ------- | -------
`name` | `string` | Nome do Usuário
`password` | `string` | Senha do Usuário. Deve ter pelo meno 6 caracteres.
`confirmPassword` | `string` | Confirmação de senha
`isAdmin` | `boolean` | É administrador?

Se o usuário for o mesmo que está logado, o campo `isAdmin` é ignorado.

Tudo estando correto, é retornado um `204 No Content`

---

`PATCH /phones/myuser`

Atualiza os dados do usuário logado. Todos os campos são opcionais, porém ao menos um deve ser informado.
(Não é possível alterar o email e o tipo de usuário)

Campo | Tipo | Descrição
------- | ------- | -------
`name` | `string` | Nome do Usuário
`password` | `string` | Senha do Usuário. Deve ter pelo meno 6 caracteres.
`confirmPassword` | `string` | Confirmação de senha

Tudo estando correto, é retornado um `204 No Content`

---
