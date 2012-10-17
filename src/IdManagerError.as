package
{
	import flash.events.Event;
	
	public class IdManagerError extends Event
	{
		public var description:String;
		
		public function IdManagerError(type:String, desc:String)
		{
			super(type);
			this.description = desc;
		}
		
		override public function clone():Event
		{
			return new IdManagerError(type, description);
		}
	}
}