import { generateBaseApiUrl } from "@/lib/utils";
import { IFinishedGoodsReceipt, IFinishedGoodsReceiptData } from "./types";
import axiosInstance from "@/lib/axios-instance";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";

export interface IGetFinishedGoodsReceiptParams extends CommonPaginationParams {
  status?: "draft" | "verified" | "approved" | "completed" | "all";
  created_date_from: string;
  created_date_to: string;
}

export const getFinishedGoodsReceipt = async (
  params: IGetFinishedGoodsReceiptParams,
): Promise<ApiResponseList<IFinishedGoodsReceipt[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/finished-goods-receipt/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getFinishedGoodsReceiptByCode = async (
  finished_goods_receipt_name: string,
): Promise<ApiResponse<IFinishedGoodsReceipt>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/finished-goods-receipt/${finished_goods_receipt_name}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createFinishedGoodsReceipt = async (
  data: IFinishedGoodsReceiptData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/finished-goods-receipt/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateFinishedGoodsReceipt = async (
  finished_goods_receipt_code: string,
  data: IFinishedGoodsReceiptData,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/finished-goods-receipt/${finished_goods_receipt_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteFinishedGoodsReceipt = async (
  finished_goods_receipt_code: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/finished-goods-receipt/${finished_goods_receipt_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCodeFinishedGoodsReceipt = async () => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/finished-goods-receipt/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
