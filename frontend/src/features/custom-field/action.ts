import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ICustomField, ICustomFielData, ISetEntityValue } from "./types";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";

export interface IGetCustomFieldParams extends CommonPaginationParams {
  entity_type: string;
  field_type: string;
  status: "active" | "inactive" | "all";
}

export const createCustomField = async (data: ICustomFielData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/custom-fields/definitions/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getCustomFields = async (
  params: IGetCustomFieldParams,
): Promise<ApiResponseList<ICustomField[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/custom-fields/definitions/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateCustomField = async (id: string, data: ICustomFielData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/custom-fields/definitions/` + id;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteCustomField = async (id: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/custom-fields/definitions/` + id;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getSchemaCustomField = async (
  entityType: string,
): Promise<{ entity_type: string; schema: ICustomField[] }> => {
  try {
    const url = generateBaseApiUrl() + `/v1/custom-fields/schema/${entityType}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getEntityValue = async (
  entity_type: string,
  entity_id: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/custom-fields/values/${entity_type}/${entity_id}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const setEntityValue = async (
  entity_type: string,
  entity_id: string,
  data: {
    values: ISetEntityValue[];
  }, // array of { field_id, value }
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/custom-fields/values/${entity_type}/${entity_id}`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};
