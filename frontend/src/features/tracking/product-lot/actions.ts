import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { IProductLotTraceability } from "./types";

export const getProductLotTracking = async (
  product_lot_code: string,
): Promise<IProductLotTraceability> => {
  const url =
    generateBaseApiUrl() + `/v1/product-lots/${product_lot_code}/traceability/`;
  const response = await axiosInstance.get(url);
  return response.data;
};

export const exportProductLot = async (
  product_lot_code: string,
): Promise<Blob> => {
  const url =
    generateBaseApiUrl() + `/v1/product-lots/${product_lot_code}/export/`;
  const response = await axiosInstance.get(url, {
    responseType: "blob",
  });
  return response.data;
};

