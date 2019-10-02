#### Introduction
The aim of this project is to ease the life of a developer by providing a simple architecture in which he can write code for a web API  (object oriented way) and serve it through a URL based service or through a multi-threaded web socket server. So you have the option to write API code once and then serve/use in multiple ways. The web-socket server in this project is built with **PHP** 7.2 using **stream sockets** and **pthreads** version 3.2.0

Please note that, "Multithreading with PTHREADS v3.2.0 and PHP 7.2.23 is very very stable. I tested on windows 10 and Server 2016. Following is the simplest example for an API class

Create a class named **Mathematics** by inheriting it from **ServiceBase** Name the file same as class name i.e; Mathematics.php

**php**

	class Mathematics extends ServiceBase {
	
	  public function add($num1,$num2) {
	      return $num1 + $num2;
	  } 
	  
	}

Thats all, what we did in above code is that we created the code to server it over JSON REST API or WebSockets.

**1) Calling "add" method from URL:**

http://localhost/myproject/service?a=Mathematics:add&p=[2,5]

Url in this format shall instantiate mathematics class using reflection and call the method "add" with given parameters 2 and 5. Return value is then sent in response. 

**2) Calling "add" method through web-sockets**

First of all run batch file "ServiceOnWebSocketThreaded.bat" this will run a PHP file in CLI mode. This is our multi-threaded websocket server.

Create an HTML file on your server or edit existing and add the following javascript code in it to call the api through web socket message.

        var ws = new WebSocket("ws://127.0.0.1:8088");
        ws.onopen = function () {
            console.log('connected');
            
            // Call the api by sending a message
            var cmd = {a:"Mathematics:add",p:[20,30]};
            ws.send(JSON.stringify(cmd));
        };

        ws.onerror = function (e) {
            console.log(e.data);
        };

        ws.onmessage = function (e) {
            // Result shall be received here
            alert(e.data);
        };

        ws.onclose = function (e) { };


Over a successfull call the API shall send a response like this:

	{"status":true,"data":"50"}

Over a faulty call the API shall send a response like this:

	{"status":false,"msg":"Invalid api/method information"}

The response shall always be a JSON encoded string where status=true shows the transaction was successfull & you can read results from **data** attribute. However if there were any errors then the status would be false and in that case read error from **msg** attribute.

#### Identifying Response Messages
In a multi-threaded web-socket application there could be a lot of parallel request and response messages. So identifying which reponse message is related to which request is critical. For this purpose the deveoper can add an optional parameter with each message **_id** in our case and the server would re-attach the same request id in response.  If your application is fairly simple and caters for one type of requests and response messages then you may not need it.  The above mathematics add method example would look like this:

	ws.onopen = function () {
		var cmd = {a:"Mathematics:add",p:[20,30],_id:"axBy1"};
		ws.send(JSON.stringify(cmd));
	};

Reponse would look like this:

	{"status":true,"data":"50","_id":"axBy1"}

#### Another way to Call the API
When a URL based approach is used to call api methods then there could be times when you may need to send large amount of data or blob to server. The large size may exeed the maximum URL limit and request may not be completed. To solve this issue the user can simply make a conventional **POST** request to server with all the parameters enclosed in request body. However in web-sockets there are no known limits.

Calling above add method in mathematics class through post will look alike this:

    URL: http://localhost/myproject/service
    Request Body: a=Mathematics:add&p=[2,5]

#### Special Considerations for WebSocket Server
I started building this on PHP 5.6 and pthreads v2.1 and the biggest problem i faced were memory leaks, memory usage kept on increasing even after threads were discarded. After researching for a few days found the best combinations that works perfectly for multi-threading. I would suggest you to use following versions as i tested over these:

    php-7.2.23-Win32-VC15-x64 (Thread Safe)
    pthreads v 3.2.0


![alt text](https://raw.githubusercontent.com/asim709/php-multithreaded-websocket-and-json-rest-server/master/WebSocket%20Diagram.png)


#### Installation Instructions
    Download all the files and place them in htdocs / web accessible location.
    Add **temp** folder in whichever folder the files are placed. It will be used to save log files.

#### Contact
If you have any questions (i dont know if github provides any feature for comments/chat) you can leave a message on:

**skype: asimishaq[at]live.com**
