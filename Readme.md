#### Introduction
The aim of this project is to ease the life of a developer by providing a simple architecture in which he can write code for JSON Rest based API (object oriented way) and serve it through either a URL or a multi-threaded web socket server. So you have the option to write API code once and then serve/use in multiple ways. The web-socket server in this project is purely built in **PHP** 7.2 using **stream sockets** and **pthreads** version 3.16 or higher. 

Please note that, "Multithreading with PTHREADS v3.2.0 and PHP 7.2.23 is very very stable unlike the older versions". Tested on windows 10 and Server 2016. Following is the simplest example for an API class

Create a class named **Mathematics** by inheriting it from **ServiceBase**

**php**

	class Mathematics extends ServiceBase {
	
	  public function add($num1,$num2) {
	      return $num1 + $num2;
	  } 
	  
	}

Thats all, what we did in above code is that we created the code to server it over JSON REST API or WebSockets.

**1) Calling "add" method from URL:**

http://localhost/myproject/service?a=Mathematics:add&p=[2,5]

Url in this format shall instentiate matematics class using reflection and call the method "add" with parameters. whatever the add returns, send it in response. 

**2) Calling "add" method through web-sockets**

First of all run batch file "ServiceOnWebSocketThreaded.bat" this will run a PHP file in CLI mode. This is our multi-threaded websocket server.

Create an HTML file on your server or edit existing and add the following javascript code in it to call the api through web socket message.

        var ws = new WebSocket("ws://127.0.0.1:8088");
        ws.onopen = function () {
            console.log('connected');
            
            // Call the API by ending a message
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
There could be times when you may need to send large amount of data or blob to server. The large size may exeed the maximum URL limit and request may not be completed. To solve this issue the user can simple make a **POST** request to server with all the parameters enclosed in request body as in any other post requests. However in web-sockets there are no known limits.

#### Special Considerations for WebSocket Server
I started building this on PHP 5.6 and pthreads v2.1 and the biggest problem i faced were memory leaks, threads seemingly terminated but memory is not released, memory keeps on increasing even after threads are discarded. After researching for a few days found the best combinations that works perfectly for multi-threading. I would suggest you to use following versions as i tested over these:

    php-7.2.23-Win32-VC15-x64 (Thread Safe)
    pthreads v 3.2.0




If you have any question (i dont know if github provides any feature for comments/chat) you can leave a message on:

**skype: asimishaq[at]live.com**
