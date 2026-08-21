export interface IVehicle {
  vehicle_id: number;
  vehicle_code: string;
  vehicle_name: string;
  brand: string;
  type: string;
  manufacture_year: number;
  license_plate: string;
  created_at: string;
}

export interface IVehicleData {
  vehicle_name: string;
  brand: string;
  type: string;
  manufacture_year: number;
  license_plate: string;
}
