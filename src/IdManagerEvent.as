package
{
	import flash.events.Event;

	public class IdManagerEvent extends Event
	{
		public var id:String;
		public var user:String;
		
		public function IdManagerEvent(type:String, user:String, id:String)
		{
			super(type);
			this.id = id;
			this.user = user;
		}
		
		override public function clone():Event
		{
			return new IdManagerEvent(type, user, id);
		}
	}
}