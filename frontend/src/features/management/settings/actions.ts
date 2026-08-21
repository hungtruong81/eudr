import { updateSetting as updateSettingApi } from "@/lib/api";

export async function updateSettings(
  data: {
    setting_code: string;
    comment: string;
    value: string;
  }[],
) {
  try {
    const res = await updateSettingApi(data);
    return res;
  } catch (error) {
    throw error;
  }
}
