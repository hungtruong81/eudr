"use client";

import { SquareSquare, Shuffle } from "lucide-react";
import { create } from "zustand";
import { persist } from "zustand/middleware";

export type DataTableConfig = typeof dataTableConfig;

export const dataTableConfig = {
  featureFlags: [
    {
      label: "Mở rộng",
      value: "floatingBar" as const,
      icon: SquareSquare,
      activeIcon: Shuffle,
      tooltipTitle: "Nút chuyển đổi mở floatingbar",
      tooltipDescription:
        "Một nút chuyển đổi mở floatingbar dưới cuối màn hình",
    },
  ],
};

type FeatureFlagValue = DataTableConfig["featureFlags"][number]["value"];

interface FeatureFlagsStore {
  featureFlags: FeatureFlagValue[];
  setFeatureFlags: (value: FeatureFlagValue[]) => void;
}

export const useFeatureFlagsStore = create<FeatureFlagsStore>()(
  persist(
    (set) => ({
      featureFlags: [],
      setFeatureFlags: (value) => set({ featureFlags: value }),
    }),
    {
      name: "feature-flags-storage",
    }
  )
);

interface FeatureFlagsProviderProps {
  children: React.ReactNode;
}

export function FeatureFlagsProvider({ children }: FeatureFlagsProviderProps) {
  return <>{children}</>;
}
