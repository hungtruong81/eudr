export type Coordinate = {
  lat: number;
  lng: number;
};

export interface IPlot {
  plot_name: string;
  coordinates: Coordinate[];
  land_area: number;
  province_id: number;
  address: string;
  harvest_weight: number;
  notes?: string;
}

export interface ITransport {
  vehicle_license_plate: string;
  driver_name: string;
  driver_phone: string;
  transport_date: string;
  pickup_time: string;
  pickup_location: string;
  delivery_time: string;
  notes?: string;
}

export interface IExternalMaterialData {
  factory_id: number;
  supplier_name: string;
  supplier_phone: string;
  supplier_address: string;
  latex_weight: number;
  latex_tsc_grade: number;
  scrap_rubber_weight: number;
  scrap_rubber_drc_grade: number;
  total_amount: number;
  purchase_date: string;
  notes?: string;
  plots: IPlot[];
  transport: ITransport;
}

export interface IExternalMaterialLand {
  external_material_land_id: number;
  external_material_id: number;
  plot_id: number;
  harvest_weight: string;
  notes: string;
  created_at: string;
  plot_code: string;
  plot_name: string;
  farmer_name: string;
  coordinates: Coordinate[];
  land_area: string;
  address: string;
  register_type: string;
  province_id: string;
  province_name: string;
}

export interface IExternalMaterialTransportDetail {
  external_material_transport_id: number;
  external_material_id: number;
  vehicle_license_plate: string;
  driver_name: string;
  driver_phone: string;
  transport_date: string;
  pickup_time: string;
  pickup_location: string;
  delivery_time: string;
  notes: string;
  created_at: string;
}

export interface IExternalMaterial {
  external_material_id: number;
  external_material_code: string;
  factory_id: number;
  factory_name: string;
  company_id: number;
  supplier_name: string;
  supplier_phone: string;
  supplier_address: string;

  latex_weight: string;
  latex_tsc_grade: string;
  scrap_rubber_weight: string;
  scrap_rubber_drc_grade: string;
  cup_lump_weight: string;

  total_amount: string;
  purchase_date: string;
  notes: string;
  status: string;

  created_by: number;
  created_at: string;
  updated_by: number;
  updated_at: string | null;

  lands: IExternalMaterialLand[];
  transport: IExternalMaterialTransportDetail;
}
