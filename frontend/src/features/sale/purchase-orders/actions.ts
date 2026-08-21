import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IPurchaseOrder } from "./types";

export interface IGetPurchaseOrderParams extends CommonPaginationParams {
  order_date_from: string;
  order_date_to: string;
  status: string;
  customer_code: string;
  order_source_type: "transaction_ticket" | "warehouse" | "product_lot";
}
export const getPurchaseOrders = async (
  params: Partial<IGetPurchaseOrderParams>,
): Promise<ApiResponseList<IPurchaseOrder[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/orders/purchases/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
