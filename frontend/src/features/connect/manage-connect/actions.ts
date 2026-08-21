import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { IConnection, ISearchUser } from "./types";

export interface IGetConnectionParams extends CommonPaginationParams {
  type?: "all" | "received" | "sent"; // Kiểu kết nối: all / received / send: Đã nhận hay đã gửi
  status?:
    | "all"
    | "pending"
    | "cancelled"
    | "accepted"
    | "rejected"
    | "blocked"; // Trạng thái: all / pending / cancelled / accepted / rejected / blocked
  account_type?: "farmer" | "purchaser" | "trader" | "company"; // Loại tài khoản: farmer / purchaser / trader / company
}

export const getConnections = async (
  params: Partial<IGetConnectionParams>,
): Promise<ApiResponseList<IConnection[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/connection/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const connectionRequest = async (data: {
  target_user_code: string;
  connection_method: "phone" | "qrcode";
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/connection/request/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelRequest = async (connection_id: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/connection/request/cancel/`;
    const response = await axiosInstance.post(url, { connection_id });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const blockRequest = async (data: {
  connection_id: string;
  action: "block";
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/connection/manage/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const respondRequest = async (data: {
  connection_id: string;
  action: "accept" | "reject";
  rejection_reason?: string;
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/connection/respond/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const searchUser = async (
  phone: string,
): Promise<{ data: ISearchUser }> => {
  try {
    const url = generateBaseApiUrl() + `/v1/connection/search/`;
    const response = await axiosInstance.get(url, { params: { phone } });
    return response.data;
  } catch (error) {
    throw error;
  }
};
