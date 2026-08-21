export interface IFile {
  file_id: number;
  file_code: string;
  file_type: string;
  file_name: string;
  file_path: string;
  file_size: number;
  image_size: string | null;
  created_at: string;
  updated_at: string;
  file_path_full: string;
}
