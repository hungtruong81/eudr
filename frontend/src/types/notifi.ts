export interface INotification {
  notification_id: number;
  user_id: number;
  type: string;
  title: string;
  message: string;
  related_id: number;
  related_type: string;
  read_at: string;
  created_at: string;
}
