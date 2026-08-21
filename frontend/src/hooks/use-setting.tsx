import { getSetting } from "@/lib/api";
import { SettingSuccessResponse } from "@/types/setting";
import { useQuery } from "@tanstack/react-query";

export function useSetting() {
  return useQuery<SettingSuccessResponse, Error>({
    queryKey: ["setting"],
    queryFn: async () => {
      const response = await getSetting();

      if (response.result === "success") {
        return response;
      }

      throw new Error(response.error?.description || "Unknown error");
    },
    staleTime: 5 * 60 * 1000,
  });
}
