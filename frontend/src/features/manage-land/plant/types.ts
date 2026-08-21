export interface IPlant {
  plant_id: number;
  plant_code: string;
  plot_code: string;
  plot_name: string;
  crop_type: string;
  year_of_planting: string;
  plantation_name: string;
  expected_harvest: string;
  plant_status: string;
  date_end_of_planting: string;
  type_of_plantation: string;
  planting_method: string;
  planting_distance: string;
  year_of_start_tapping: string;
  year_of_upward_tapping: string;
  percentage_of_trees_meeting_perimeter_standards: string;
  denity_of_tapping_tree: number;
  tapping_method: string;
  annual_yield: number;
  clone_type_of_tree: string;
  effective_tree_density: number;
  standard_deviation: string;
  production_24: string;
  created_at: string;
  signature_file_id?: number;
}
