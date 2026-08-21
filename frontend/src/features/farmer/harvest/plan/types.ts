export interface IHarvestPlan {
  harvest_plan_id: number;
  harvest_plan_code: string;
  farmer_name: string;
  contract_code: string;
  harvest_start_date: string; // YYYY-MM-DD
  harvest_end_date: string; // YYYY-MM-DD
  tapping_regime: string;
  expected_yield: string; // dạng "20000.00"
  actual_yield: number;
  schedule_count: number;
  harvest_count: number;
  eudr_status: number;
  notes: string;
  lands: IHarvestPlanLand[];
  created_at: string; // YYYY-MM-DD HH:mm:ss
}

export interface IHarvestPlanLand {
  plot_id: number;
  plot_code: string;
  plot_name: string;
}

export interface IHarvestPlanData {
  harvest_start_date: string; // Ngày bắt đầu thu hoạch
  harvest_end_date: string; // Ngày kết thúc thu hoạch
  tapping_regime: string; // Chế độ cạo - (D1/D2/D3/D4/Flexible)
  contract_code: string; // Mã hợp đồng
  plot_ids: number[]; // Danh sách đất cần lên kế hoạch thu hoạch. Fill theo Hợp đồng
  expected_yield: string; // Tổng sản lượng dự kiến sẽ cung cấp (Số lượng dự kiến) - Kg. Fill theo Hợp đồng
  notes: string; // Ghi chú nếu có
}

export interface ISchedule {
  harvest_schedule_id: number;
  harvest_schedule_code: string;
  harvest_plan_id: number;
  plot_id: number;
  pickup_date: string;
  pickup_time: string;
  expected_yield: string;
  actual_yield: string;
  notes: string;
  company_id: number;
  buyer_company_id: number;
  buyer_user_id: number;
  created_by: number;
  created_at: string;
  updated_by: number;
  updated_at: string;
  deleted_by: number;
  deleted_at: string;
  harvest_plan_code: string;
  plot_code: string;
  plot_name: string;
  tapping_regime: string;
}

export interface IScheduleById {
  harvest_schedule_id: number;
  harvest_schedule_code: string;
  harvest_plan_id: number;
  harvest_plan_code: string;
  plot_id: number;
  plot_name: string;
  pickup_date: string;
  pickup_time: string;
  expected_yield: string;
  actual_yield: number;
  notes: string;
}

export interface IScheduleData {
  harvest_plan_code: string;
  schedules: {
    plot_id: number; // Đất thu hoạch
    pickup_date: string; // Ngày thu hoạch
    pickup_time: string; // Thời gian thu hoạch (Thời gian cạo)
    expected_yield: number; // Sản lượng thu hoạch dự kiến (Kg)
  }[];
}
