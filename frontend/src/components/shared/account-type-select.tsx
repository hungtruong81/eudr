"use client";

import React from "react";

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Label } from "@/components/ui/label";

const accountTypeLabels: Record<any, string> = {
  all: "Tất cả",
  farmer: "Nông hộ",
  purchaser: "Thu mua",
  transport: "Vận chuyển",
  factory: "Nhà máy",
  sales: "Bán hàng",
};

interface AccountTypeSelectProps {
  value?: any;
  onChange?: (value: any) => void;
  label?: string;
  className?: string;
  hideAllOption?: boolean;
}

const AccountTypeSelect = ({
  value = "all",
  onChange,
  label = "Loại tài khoản",
  className,
  hideAllOption = false,
}: AccountTypeSelectProps) => {
  const options = (Object.keys(accountTypeLabels) as any[]).filter((key) =>
    hideAllOption ? key !== "all" : true,
  );

  return (
    <div className="space-y-2">
      <Label>{label}</Label>

      <Select value={value} onValueChange={(val) => onChange?.(val as any)}>
        <SelectTrigger className={className ?? "w-[180px]"}>
          <SelectValue placeholder="Chọn loại tài khoản" />
        </SelectTrigger>

        <SelectContent>
          {options.map((value) => (
            <SelectItem key={value} value={value}>
              {accountTypeLabels[value]}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
};

export default AccountTypeSelect;
