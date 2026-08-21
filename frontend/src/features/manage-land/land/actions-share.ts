import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IListLandShareByUser, IPlot } from "./types";

export interface ILandShareData {
  shared_with_user_code: string;
  plot_ids: number[];
}

export const landShare = async (data: ILandShareData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/share/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export interface IGetListLandShareByUserParams extends CommonPaginationParams {
  shared_with_user_code: string;
  status: "all" | "active" | "revoked";
}
export const listLandShareByUser = async (
  params: Partial<IGetListLandShareByUserParams>,
): Promise<ApiResponseList<IListLandShareByUser[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/share/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export interface IGetMyLandShare extends CommonPaginationParams {
  owner_id: number;
  status: "all" | "active" | "revoked";
}
export const myLandShare = async (
  params: Partial<IGetMyLandShare>,
): Promise<ApiResponseList<IPlot[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/my/shared-lands/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const revokeShareLand = async (data: {
  plot_code: string;
  shared_with_user_code: string;
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/revoke/share`;
    const response = await axiosInstance.delete(url, { data });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const landShareAll = async (
  params: CommonPaginationParams,
): Promise<ApiResponseList<IListLandShareByUser[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/share/all/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const listUserSharedLand = async (
  plot_code: string,
  params: CommonPaginationParams,
): Promise<{
  total_records: number;
  records: {
    user_id: number;
    user_code: string;
    full_name: string;
    phone: string;
    email: string;
    register_type: string;
  }[];
}> => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/${plot_code}/shares/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
