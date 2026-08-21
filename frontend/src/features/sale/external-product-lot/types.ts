export interface ILandItem {
  plot_id?: number;
  plot_name: string;
  province_id: number;
  land_area: number;
  harvest_weight: number;
  address: string;
  notes: string;
  coordinates: { lat: number; lng: number }[];
}

export interface ITransport {
  vehicle_license_plate: string;
  driver_name: string;
  driver_phone: string;
  transport_date: string; // YYYY-MM-DD
  pickup_time: string; // HH:mm
  pickup_location: string;
  delivery_time: string; // HH:mm
  delivery_location: string;
  notes: string;
}

export interface IExternalProductLotData {
  supplier_company_name: string;
  supplier_factory_name: string;
  supplier_phone: string;
  supplier_address: string;
  original_product_lot_code: string;
  factory_id: number;
  grade: string;
  total_blocks: number;
  total_weight: number;
  production_date_from: string; // YYYY-MM-DD
  production_date_to: string; // YYYY-MM-DD
  purchase_date: string; // YYYY-MM-DD
  purchase_amount: number;
  notes: string;
  lands: ILandItem[];
  transport: ITransport;
}

export interface ICoordinate {
  lat: number;
  lng: number;
}

export interface IProductLotLand {
  product_lot_land_id: number;
  product_lot_id: number;
  plot_id: number;
  harvest_weight: string; // BE trả string
  notes: string;
  created_at: string;
  plot_code: string;
  plot_name: string;
  farmer_name: string;
  coordinates: ICoordinate[];
  land_area: string;
  address: string;
  eudr_status: number;
  register_type: string;
  province_id: number;
  province_name: string;
}

export interface ITransport {
  vehicle_license_plate: string;
  driver_name: string;
  driver_phone: string;
  transport_date: string;
  pickup_time: string;
  pickup_location: string;
  delivery_time: string;
  delivery_location: string;
  notes: string;
}

export interface IProductLotExternal {
  product_lot_id: number;
  product_lot_code: string;
  lot_type: string;
  grade: string;
  factory_id: number;
  owner_company_id: number;

  factory_name: string | null;
  factory_code: string | null;

  production_date_from: string;
  production_date_to: string;

  total_blocks: number;
  total_weight: string; // BE trả string

  status: string;
  confirmed_at: string | null;

  created_by: number;
  updated_by: number;

  supplier_company_name: string;
  supplier_factory_name: string;
  supplier_phone: string;
  supplier_address: string;

  original_product_lot_code: string;

  purchase_date: string;
  purchase_amount: number;

  notes: string;

  lands: IProductLotLand[];
  transport: ITransport | null;
}

export interface INonEudrProductLotPayload {
  supplier_company_name: string;
  supplier_factory_name: string;
  supplier_phone: string;
  external_contract_code: string;
  factory_id: number;
  production_date_from: string;
  production_date_to: string;
  notes: string;
  product_lots: {
    product_lot_code: string;
    quantity: number;
    unit: string;
    weight: number;
    notes?: string;
  }[];
  contract_file_ids: number[];
  signature_file_id: number;
}
