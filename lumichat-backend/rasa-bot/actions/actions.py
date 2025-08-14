from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet

class ActionSetSessionSummary(Action):
    def name(self) -> Text:
        return "action_set_session_summary"

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any]
    ) -> List[Dict[Text, Any]]:
        user_message = tracker.latest_message.get("text", "")
        keywords: List[str] = []

        msg = user_message.lower()
        if "sad" in msg or "depress" in msg:
            keywords.append("Sad")
        if "anxious" in msg or "anxiety" in msg:
            keywords.append("Anxious")
        if "help" in msg:
            keywords.append("Needs Help")
        if "stressed" in msg:
            keywords.append("Stressed")
        if "lonely" in msg:
            keywords.append("Lonely")
        # add more patterns as desired…

        title = ", ".join(keywords) if keywords else "General Conversation"
        return [SlotSet("session_summary", title)]
