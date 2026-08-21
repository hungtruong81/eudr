import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import {
  IExternalProductLotData,
  INonEudrProductLotPayload,
  IProductLotExternal,
} from "./types";

export interface IGetExternalProductLotParams extends CommonPaginationParams {
  production_date_from?: string;
  production_date_to?: string;
  status?: "draft" | "confirmed" | "shipped" | "cancelled" | "all";
  lot_type?: "internal" | "external" | "all";
}

export const getExternalProductLots = async (
  params: IGetExternalProductLotParams,
): Promise<ApiResponseList<IProductLotExternal[]>> => {
  const url = generateBaseApiUrl() + `/v1/product-lots/`;
  const res = await axiosInstance.get(url, { params });
  return res.data;
};

export const importExternalProductLot = async (payload: {
  file: File;
  supplier_company_name: string;
  is_eudr: boolean;
  external_contract_code: string;
  external_system: string;
  product_lot?: string;
  quantity?: number;
  production_date?: string;
  document_file_ids: string[];
  signature_file_id: string;
}) => {
  const url = generateBaseApiUrl() + `/v1/product-lots/import`;
  const formData = new FormData();
  formData.append("file", payload.file);
  formData.append("supplier_company_name", payload.supplier_company_name);
  formData.append("is_eudr", String(payload.is_eudr));
  formData.append("external_contract_code", payload.external_contract_code);
  formData.append("external_system", payload.external_system);

  if (payload.product_lot) {
    formData.append("product_lot", payload.product_lot);
  }
  if (payload.quantity !== undefined) {
    formData.append("quantity", String(payload.quantity));
  }
  if (payload.production_date) {
    formData.append("production_date", payload.production_date);
  }

  payload.document_file_ids.forEach((id) => {
    formData.append("document_file_ids", id);
  });
  formData.append("signature_file_id", payload.signature_file_id);

  const res = await axiosInstance.post(url, formData);
  return res.data;
};

export const importNonEudr = async (data: INonEudrProductLotPayload) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/import/non-eudr`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createExternalProductLot = async (
  data: IExternalProductLotData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/`;
    const res = await axiosInstance.post(url, data);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const updateExternalProductLot = async (
  id: string,
  data: IExternalProductLotData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/${id}`;
    const res = await axiosInstance.put(url, data);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const deleteExternalProductLot = async (id: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/external/${id}`;
    const res = await axiosInstance.delete(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const confirmExternalProductLot = async (id: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/product-lots/external/${id}/confirm`;
    const res = await axiosInstance.put(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const cancelExternalProductLot = async (id: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/external/${id}/cancel`;
    const res = await axiosInstance.put(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};

export const getExternalProductLotById = async (id: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/product-lots/${id}`;
    const res = await axiosInstance.get(url);
    return res.data;
  } catch (error) {
    throw error;
  }
};
