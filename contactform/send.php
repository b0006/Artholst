<?php

// Адрес, куда отправляем письмо
$to = 'photo_na_kholste@mail.ru';

// Получаем данные от пользователя
// Все данные обязательно нужно проверять на правильность!
$userEmail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$subject = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$option = filter_input(INPUT_POST, 'option', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$social = filter_input(INPUT_POST, 'social', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$message = strip_tags(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_MAGIC_QUOTES), '<p><a><b><div>');
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

$head = 'Заявка Art Holst';

// Проверка, что данные не пустые. 
// Нас не интересуют анонимки
if (empty($userEmail)) {
    die('Отсутствует или неверен адрес почты.');
// Нас не интересуют послания с пустым сообщением
} elseif (empty($name)) {
    die('Введите Ваше имя, пожалуйста');
} elseif (empty($phone)) {
    die('Введите Ваш номер телефона, пожалуйста');
}

$the_file = '';
//Если пользователь выбрал файл для отправки
if (!empty($_FILES['picture']['tmp_name'])) {
    // Закачиваем файл
    $path = $_FILES['picture']['name'];
    if (copy($_FILES['picture']['tmp_name'], $path)) {
        $the_file = $path;
    }
}
// Если есть прикреплённый файл, то заголовки чуть другие.
// Поэтому, в зависимости от того, отправил ли пользователь файл,
// выбираем, что делать дальше
$headers = null;

if (empty($the_file)) {
    // эта часть кода отвечает за отправку сообщений без вложений
    // собираем заголовки
    $headers = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: $head <akasikov@yandex.ru>";
    $headers[] = "Bcc: JJ Chong akasikov@yandex.ru<akasikov@yandex.ru>";
    $headers[] = "Reply-To: Recipient Name <akasikov@yandex.ru>";
    $headers[] = "Subject: {$head}";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    // собираем текст письма
    $allmsg = "<p><b>E-mail:</b> $userEmail</p>
        <p><b>Тип картины:</b> $option</p>
			<p><b>Телефон:</b> $phone</p>
			<p><b>Социальная сеть:</b> $social</p>
            <p><b>Сообщение от клиента:</b> $message</p>";
    $allmsg = "<html><head><title>Обратная связь</title><META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\"></head><body>" . $allmsg . "</body></html>";
    // отправляем
    if (!mail($to, $head, $allmsg, implode("\r\n", $headers))) {
        echo 'Письмо не отправлено - что-то не сработало.';
    } else {
        echo 'Заявка отправлена (без картинки).';
    }
} else {
    // эта часть кода отвечает за отправку сообщений без вложений
    // читаем отправляемый файл в строку
    $fp = fopen($the_file, "r");
    if (!$the_file) {
        die("Ошибка отправка письма: Файл $the_file не может быть прочитан.");
    }
    $file = fread($fp, filesize($path));
    fclose($fp);
    // удаляем временный файл
    unlink($path);
    // собираем текст письма
    $allmsg = "<p><b>E-mail:</b> $userEmail</p>
        <p><b>Выбранная опция:</b> $option</p>
			<p><b>Телефон:</b> $phone</p>
			<p><b>Социальная сеть:</b> $social</p>
            <p><b>Сообщение от клиента:</b> $message</p>";
    $allmsg = "<html><head><title>Обратная связь</title><META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\"></head><body>" . $allmsg . "</body></html>";
    // генерируем разделитель
    $boundary = "--" . md5(uniqid(time()));
    // собираем заголовки
    $headers = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "From: $head <akasikov@yandex.ru>";
    $headers[] = "Bcc: JJ Chong akasikov@yandex.ru<akasikov@yandex.ru>";
    $headers[] = "Reply-To: Recipient Name akasikov@yandex.ru<akasikov@yandex.ru>";
    $headers[] = "Subject: {$head}";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
    // собираем текст письма + приложенынй файл
    $multipart = array();
    $multipart[] = "--$boundary";
    $multipart[] = "Content-Type: text/html; charset=UTF-8";
    $multipart[] = "Content-Transfer-Encoding: Quot-Printed\r\n";
    $multipart[] = "$allmsg\r\n";
    $multipart[] = "--$boundary";
    $multipart[] = "Content-Type: application/octet-stream";
    $multipart[] = "Content-Transfer-Encoding: base64";
    $multipart[] = "Content-Disposition: attachment; filename = \"" . $path . "\"\r\n";
    $multipart[] = chunk_split(base64_encode($file));
    $multipart[] = "--$boundary";
    // отправляем
    if (!mail($to, $head, implode("\r\n", $multipart), implode("\r\n", $headers))) {
        echo 'Письмо не отправлено - что-то не сработало.';
    } else {
        echo 'Заявка отправлена. Мы скоро с Вами свяжемся. Спасибо :)';
    }
}
?>