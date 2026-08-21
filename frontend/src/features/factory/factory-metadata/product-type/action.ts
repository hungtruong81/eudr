import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { CommonPaginationParams } from "@/types/api";
import { IProductTypeData } from "./types";

export interface IGetProductTypeParams extends CommonPaginationParams {
  product_type_category?: "scrap_rubber" | "concentrated_latex";
}

export const getProductTypes = async (params: IGetProductTypeParams) => {
  try {
    const url = generateBaseApiUrl() + "/v1/product-type/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductType = async (data: IProductTypeData) => {
  try {
    const url = generateBaseApiUrl() + "/v1/product-type/";
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductType = async (
  product_type_code: string,
  data: IProductTypeData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-type/${product_type_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductType = async (product_type_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-type/${product_type_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
