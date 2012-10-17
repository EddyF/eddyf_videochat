/**
 * ADOBE SYSTEMS INCORPORATED
 * Copyright 2008-2011 Adobe Systems Incorporated
 * All Rights Reserved.
 *
 * NOTICE: Adobe permits you to use, modify, and distribute this file
 * in accordance with the terms of the license agreement accompanying it.
 * 
 * Author: Jozsef Vass
 * 
 * Protocol description. This is a very simple protocol for user registration 
 * (or unregistration) and lookup.
 * 
 * For registration, client sends following query string to web-service:
 * 
 * GET cgi-bin/reg.cgi?username=user&identity=peer_id_of_user
 * 
 * Server should respond 200 OK with message body:
 * <?xml version="1.0" encoding="utf-8"?>
 * <result>
 *   <update>true</update>
 * </result>
 * 
 * For unregistration, client sends following request:
 * 
 * GET cgi-bin/reg.cgi?username=user&identity=0 HTTP/1.1
 * 
 * Server response is same as for registration. Registration is refreshed 
 * every 30 minutes.
 * 
 * For user lookup, client sends following request (to avoid caching,
 * request is randomized using time, etc.):
 * 
 * GET cgi-bin/reg.cgi?friends=remote_user HTTP/1.1
 * 
 * If remote user is available, server responds 200 OK with following message body:
 * 
 * <?xml version="1.0" encoding="utf-8"?>
 * <result>
 *   <friend>
 *     <user>remote_user</user>
 *     <identity>peer_id_of_remote_user</identity>
 *   </friend>
 * </result>
 * 
 * If remote user is not available, server responds with 200 OK with following 
 * message body:
 * <?xml version="1.0" encoding="utf-8"?>
 * <result>
 *   <friend>
 *     <user>remote_user</user>
 *   </friend>
 * </result>
 */
 
 package
 {
 	import flash.events.Event;
 	import flash.events.TimerEvent;
 	import flash.utils.Timer;
 	
 	import mx.rpc.events.FaultEvent;
 	import mx.rpc.events.ResultEvent;
 	import mx.rpc.http.HTTPService;
			
 	public class HttpIdManager extends AbstractIdManager
 	{	
 		private var mHttpService:HTTPService = null;
 		
 	 	private var mWebServiceUrl:String = "";
		
		private var mConnectionTimer:Timer;
		private var mUser:String;
		private var mId:String;
		
		override protected function doSetService(service:Object):void
		{
			mWebServiceUrl = service as String;
		}
 		
 		override protected function doRegister(user:String, id:String):void
 		{
 			if (mWebServiceUrl.length == 0 || user.length == 0 || id.length == 0)
 			{
				var e:Event = new IdManagerError("registerFailure", "Empty web service URL, user or id");
 				dispatchEvent(e);
 				return;		
 			}
 			
 			mUser = user;
 			mId = id;
 			
 			// register id to http service
            mHttpService = new HTTPService();
            mHttpService.url = mWebServiceUrl;
            mHttpService.addEventListener("result", httpResult);
            mHttpService.addEventListener("fault", httpFault);
                
            var request:Object = new Object();
            request.username = user
            request.identity = id;
            mHttpService.cancel();
            mHttpService.send(request);
                
            // refresh registration with web service in every 30 minutes
			mConnectionTimer = new Timer(1000 * 60 * 30);
			mConnectionTimer.addEventListener(TimerEvent.TIMER, onConnectionTimer);
            mConnectionTimer.start();
 		}
 		
 		override protected function doLookup(user:String):void
 		{
 			if (mHttpService)
 			{
 				var request:Object = new Object();
				request.friends = user;
				// when making repeated calls to same user, it seemed that
				// we recived cached result. So add time, to it to make it unique.
				var now:Date = new Date();
				request.time = now.getTime();
				mHttpService.cancel();
				mHttpService.send(request);
 			}
 			else
 			{
 				var e:Event = new IdManagerError("lookupFailure", "HTTP service not created");
 				dispatchEvent(e);
 			}
 		}
 		
 		override protected function doUnregister():void
 		{
 			if (mHttpService)
			{
				var request:Object = new Object();
				request.username = mUser;
				request.identity = "0";
				mHttpService.cancel();
				mHttpService.send(request);
			}
					
			if (mConnectionTimer)
			{
 				mConnectionTimer.stop();
 				mConnectionTimer = null;
 			}	
 		}
 		
 		// we need to refresh regsitration with web service periodically
		private function onConnectionTimer(e:TimerEvent):void
		{		
			var request:Object = new Object();
            request.username = mUser;
           	request.identity = mId;
           	var now:Date = new Date();
			request.time = now.getTime();
            mHttpService.cancel();
            mHttpService.send(request);
		}

 		// process error from web service
		private function httpFault(e:FaultEvent):void
		{	
			var d:IdManagerError = new IdManagerError("idManagerError", "HTTP error: " + e.message.toString());
 			dispatchEvent(d);
		}
		
		// process successful response from web service		
		private function httpResult(e:ResultEvent):void
		{	
			var result:Object = e.result as Object;
			var remote:Object;
			if (result.hasOwnProperty("result"))
			{
				if (result.result.hasOwnProperty("update"))
				{
					// registration response
					if (result.result.update == true)
					{
						var d:Event = new Event("registerSuccess");
 						dispatchEvent(d);
					}
					else
					{
						d = new IdManagerError("registerFailure", "HTTP update error");
 						dispatchEvent(d);
					}
				}
				else if (result.result.hasOwnProperty("friend"))
				{
					// party query response
					remote = result.result.friend as Object;
					if (remote.hasOwnProperty("user") && remote.hasOwnProperty("identity"))
					{
						var identityString:String = remote.identity
						var userString:String = remote.user;
						
						var r:IdManagerEvent = new IdManagerEvent("lookupSuccess", userString, identityString);
						dispatchEvent(r);
					}
					else if (remote.hasOwnProperty("user"))
					{
						userString = remote.user;
						r = new IdManagerEvent("lookupSuccess", userString, "");
						dispatchEvent(r);
					}
					else
					{
						d = new IdManagerError("lookupFailure", "HTTP response does not have user property");
 						dispatchEvent(d);
					}
				}
				else
				{
					d = new IdManagerError("idManagerError", "Unhandeled HTTP response");
 					dispatchEvent(d);
				}
			}
			else
			{
				d = new IdManagerError("idManagerError", "HTTP response has no result");
 				dispatchEvent(d);

			}
		}
 	}
 }
