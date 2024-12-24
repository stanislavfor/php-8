## Основы PHP.
### Урок 16. Семинар. Учимся собирать логи, дебажим приложение
<br>
<br>
<br>
<br>
В уже созданных маршрутах попробуйте вызывать их с некорректными данными. <br>

- Что будет происходить? Будут ли появляться ошибки?
  <br>

- При появлении ошибок, произведите их анализ.  <br>
  Обязательно зафиксируйте шаги своих размышлений.
  <br>

- На основании анализа произведите устранение.

<hr>
<hr>
#### Примечание

1. Открыть файл `hosts`по адресу:
   ```
   C:\Windows\System32\drivers\etc\hosts
   ```
2. В файл добавить строку:
   ```
   127.0.0.1 mysite.local
   ```

3.  Запустить Docker Desktop, если Docker и Docker Compose уже установлены.


4. Перейти в VSCode в меню "Terminal" (Терминал) и выберать "New Terminal" (Новый терминал).
   
5. Ввести команду для проверки версии Docker:   
  
```
docker --version
```

6. Ввести команду для запуска контейнеров:

```   
   docker-compose up
```