The aim of this project is to ease the life of a developer by providing a simple architecture in which he can write code for JSON Rest based API (object oriented way) and serve it through either a URL or a multi-threaded web socket server. So you have the option to write API code once and then serve/use in multiple ways. The web-socket server in this project is purely built in PHP 7.2 using STREAM SOCKET, PTHREADS version 3.16 or higher. 

Following is the simplest example for an API class

PHP Code:
Create a class named Mathematics by inheriting it from ServiceBase

class Mathematics extends ServiceBase {

  public function add($num1,$num2) {
      return $num1 + $num2;
  }
 
}

Thats all, what we did in above code is that we created the code to server it over JSON REST API or WebSockets.

1) Calling add method from URL:

http://localhost/myproject/service?a=Mathematics:add&p=[2,5]

Url is this format shall instentiate Matematics class using Reflection and call the method "add" with parameters. Whatever the add returns, send it in response. 

2) Calling "add" method through web-sockets

First of all run batch file "ServiceOnWebSocketThreaded.bat" this will run a PHP file in CLI mode. This is our multi-threaded websocket server.

Create an HTML file on your server or edit existing and add the following javascript code in it to call the api through web socket message.














