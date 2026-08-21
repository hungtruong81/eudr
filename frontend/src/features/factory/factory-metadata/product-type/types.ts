export interface IProductType {
  product_type_id: number;
  product_type_code: string;
  product_type_name: string;
  product_type_category: string;
  product_weight: string;
  description: string;
}

export interface IProductTypeData {
  product_type_name: string; // Tên loại sản phẩm
  product_type_code: string; // Mã loại sản phẩm
  product_type_category: string; // Danh mục loại sản phẩm mủ tạp: scrap_rubber, Kem: concentrated_latex
  product_weight: string; // Khối lượng
  description: string; // Mô tả (Nếu có)
}
