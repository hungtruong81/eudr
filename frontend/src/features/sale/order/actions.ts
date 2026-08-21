import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IOrder, IOrderData } from "./types";

export interface IGetOrderParams extends CommonPaginationParams {
  order_date_from: string;
  order_date_to: string;
  status: string;
  customer_code: string;
  order_source_type: "transaction_ticket" | "warehouse";
  buyer_type: "trader" | "customer";
}
export const getOrders = async (
  params: Partial<IGetOrderParams>,
): Promise<ApiResponseList<IOrder[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/orders/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getOrderByCode = async (
  sale_order_code: string,
): Promise<{ order: IOrder }> => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/orders/${sale_order_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createOrder = async (data: IOrderData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/orders/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateOrder = async (data: IOrderData) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/sales/orders/${data.sale_order_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteOrder = async (sale_order_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/orders/${sale_order_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCode = async () => {
  try {
    const url = generateBaseApiUrl() + `/v1/sales/orders/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelOrder = async (sale_order_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/sales/orders/${sale_order_code}/cancel`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const approveOrder = async (sale_order_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/sales/orders/${sale_order_code}/approve`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
