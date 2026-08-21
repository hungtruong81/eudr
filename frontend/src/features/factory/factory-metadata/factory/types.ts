export interface IFactory {
  factory_id: number;
  factory_code: string;
  factory_name: string;
  address: string;
  created_at: string;
}

export interface IFactoryData {
  factory_name: string;
  address: string;
  notes: string;
}
