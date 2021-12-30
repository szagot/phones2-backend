# App para controle de contatos de campo / revisitas

Projeto clonado de [new-apps](https://github.com/szagot/new-apps)

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

## Serviços de Contato

`GET /phones/contacts`

Pega todos os contatos. Os campos são simplificados para listagem.

Em caso de sucesso, o retorno será o seguinte:

```JSON
[
    {
        "phone": 11988889999,
        "formatted": "(11) 98888-9999",
        "international": "+5511988889999",
        "updatedAt": "2021-12-08 14:30:41",
        "brazilDate": "08/12/21 14:30",
        "allowCall": true,
        "hasRevisit": true
    }
]
```

***Obs:** O campo `allowCall` indica qualdo é permitido fazer ligação para um contato, e `hasRevisit` indica quando o contato possui um nome de Morador*

---

`GET /phones/contacts/call`

Retorna apenas os números elegíveis para ligação.

Em caso de sucesso, o retorno será o seguinte:

```JSON
[
    {
        "phone": 11988889999,
        "formatted": "(11) 98888-9999",
        "international": "+5511988889999",
        "updatedAt": "2021-07-08 14:30:41",
        "brazilDate": "08/07/21 14:30",
        "allowCall": true,
        "hasRevisit": true
    }
]
```

---

`GET /phones/contacts/revisits`

Retorna apenas os números que possuem revisitas

Em caso de sucesso, o retorno será o seguinte:

```JSON
[
    {
        "phone": 11988889999,
        "formatted": "(11) 98888-9999",
        "international": "+5511988889999",
        "resident": "Nome do Morador",
        "publisher": "Nome do Pulicador",
        "updatedAt": "2021-07-08 14:30:41",
        "brazilDate": "08/07/21 14:30"
    }
]
```

---

`GET /phones/contacts/{id}`

Retorna os detalhes de um número de telefone.

O {id} é o número do telefone com ddd (apenas núemeros). Exemplo: `{uri}/phones/contacts/11988889999`.

Em caso de sucesso o retorno será:

```JSON
{
    "id": 11988889999,
    "ddd": 11,
    "prefix": 98888,
    "sufix": 9999,
    "formatted": "(11) 98888-9999",
    "international": "+5511988889999",
    "resident": "Nome do Morador",
    "publisher": "Nome do Publicador",
    "dayOfWeek": 1,
    "dayOfWeekText": "Domingo",
    "period": 1,
    "periodText": "Manhã",
    "updatedAt": "2021-12-27 17:50:56",
    "brazilDate": "27/12/2021 17:50"
}
```

***Obs:** `dayOfWeek` refere-se ao dia da semana marcado para retorno, e `period` ao período do dia para esse retorno (manhã, tarde ou noite).*

Ao pegar os dados de um usuário, ele fica bloqueado. De forma que apenas o usuário atual consegue pegar os dados, outro usuário não consegue, sendo que o retorno será:

```JSON
{
    "message": "O número 11988889999 já está em uso pelo usuário email@email.com",
    "status": 404,
    "timestamp": 1640888204
}
```

Para liberar o contato, utilize o serviço `/phones/contacts/free/{id}`, explicado a seguir.

---

`GET /phones/contacts/free/{id}`

Libera o número para uso de outro usuário.

O campo retornado é `free`, que será `true` caso a liberação tenha sido bem sucedida.

```JSON
{
    "free": true
}
```

---

`DELETE /phones/contacts/{id}`

Apaga os dados do contato informado.

Se o mesmo tiver notas de revisitas, estas também serão apagadas.

---

`POST /phones/contacts`

Insere um número de telefone ou uma sequencia de números

Campo | Tipo | Descrição
------- | ------- | -------
`ddd` | `int` | DDD
`prefix` | `int` | Prefixo do número
`sufixStart` | `int` | Sufixo do número inicial
`sufixEnd` | `int` | (Opcional) Sufixo do número final

Se for cadastrar apenas um número, o campo `sufixEnd` pode ser omitido, ou declarado com o mesmo valor de `sufixStart`.

Se tudo ocorrer bem, será retornada uma lista dos números inseridos, como segue:

```JSON
[
    {
        "phone": 11988889999,
        "formatted": "(11) 98888-9999",
        "international": "+5511988889999",
        "updatedAt": "2021-12-27 19:38:40",
        "brazilDate": "27/12/21 19:38",
        "hasRevisit": false
    }
]
```

---

`PATCH /phones/contacts/{id}`

Atualiza os dados de um contato. Todos os campos são opcionais, porém ao menos um deve ser informado.

Campo | Tipo | Descrição
------- | ------- | -------
`resident` | `string` | Nome do Morador
`publisher` | `string` | Nome do Publicador
`dayOfWeek` | `int` | Dia da semana, de `1` a `7`, sendo `1` = Domingo.
`period` | `int` | Perídodo. `1` = Manhã, `2` = Tarde e `3` = Noite

Tudo estando correto, é retornado um `204 No Content`

---

`GET /phones/notes/{contactId}`

Retorna as notas de um contato.

O {contactId} é o número do telefone com ddd (apenas núemeros). Exemplo: `{uri}/phones/notes/11988889999`.

Em caso de sucesso o retorno será:

```JSON
[
    {
        "id": 642,
        "dateContact": "2021-10-03 10:39:18",
        "brazilDate": "03/10/2021 10:39",
        "text": "Morador não atendeu"
    }
]
```

---

`DELETE /phones/notes/{id}`

Apaga uma nota específica. Neste caso o {id} é o da nota em questão.

---

`POST /phones/notes/{contactId}`

Insere uma observação para o contato {contactId}

Campo | Tipo | Descrição
------- | ------- | -------
`contactDate` | `string` | Data do contato (2099-12-31 23:59)
`text` | `string` | Texto da observação

Se tudo ocorrer bem, será retornada a observação inserida:

```JSON
{
    "id": 642,
    "dateContact": "2021-10-03 10:39:18",
    "brazilDate": "03/10/2021 10:39",
    "text": "Morador não atendeu",
    "contactId": "11988889999"
}
```

---

`PATCH /phones/notes/{id}`

Atualiza os dados de uma observação de contato. Todos os campos são opcionais, porém ao menos um deve ser informado.

Campo | Tipo | Descrição
------- | ------- | -------
`contactDate` | `string` | Data do contato (2099-12-31 23:59)
`text` | `string` | Texto da observação

Tudo estando correto, é retornado um `204 No Content`
