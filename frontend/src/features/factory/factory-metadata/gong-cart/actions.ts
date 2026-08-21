import { generateBaseApiUrl } from "@/lib/utils";
import axiosInstance from "@/lib/axios-instance";
import { ApiResponse, ApiResponseList } from "@/types/api";
import { IProductionGongCart } from "./types";
import { CommonPaginationParams } from "@/types/api";

interface IGetProductionGongCartsParams extends CommonPaginationParams {
  factory_id?: number;
  status?: string; //available,in_use,cleaning,all
}

export const getProductionGongCarts = async (
  params: IGetProductionGongCartsParams,
): Promise<ApiResponseList<IProductionGongCart[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-gong-cart/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getProductionGongCartByCode = async (
  gong_cart_code: string,
): Promise<ApiResponse<IProductionGongCart>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-gong-cart/${gong_cart_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductionGongCart = async (data: IProductionGongCart) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-gong-cart/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductionGongCart = async (
  gong_cart_code: string,
  data: IProductionGongCart,
) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-gong-cart/${gong_cart_code}`;
    const response = await axiosInstance.patch(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductionGongCart = async (gong_cart_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-gong-cart/${gong_cart_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateProductionGongCartCode = async (): Promise<
  ApiResponse<{ gong_cart_code: string }>
> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-gong-cart/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
