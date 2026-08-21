import React from "react";
import GeneralSettingsForm from "@/features/management/settings/components/general-settings-form";
import { Metadata } from "next";

export const metadata: Metadata = {
  title: "Cấu hình chung | EUDR",
  description: "Quản lý cấu hình chung hệ thống",
};

export default function GeneralSettingsPage() {
  return <GeneralSettingsForm />;
}
