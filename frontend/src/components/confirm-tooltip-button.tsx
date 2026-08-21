"use client";
import React from "react";
import { Popconfirm } from "antd";
import { TooltipButton } from "@/components/tooltip-button";
import { ButtonProps } from "antd";
import { useTranslations } from "next-intl";

interface ConfirmTooltipButtonProps extends ButtonProps {
  tooltip: string;
  confirmTitle?: string;
  confirmDescription?: string;
  onConfirm: () => Promise<void> | void;
  icon?: React.ReactNode;
}

export const ConfirmTooltipButton: React.FC<ConfirmTooltipButtonProps> = ({
  tooltip,
  confirmTitle = "Xác nhận",
  confirmDescription = "Bạn có chắc chắn muốn thực hiện hành động này?",
  onConfirm,
  ...buttonProps
}) => {
  const tc = useTranslations("Common");
  return (
    <Popconfirm
      title={confirmTitle}
      description={confirmDescription}
      onConfirm={onConfirm}
      okText={tc("confirm")}
      cancelText={tc("cancel")}
      placement="topRight">
      {/* Bao bọc một span/div để đảm bảo an toàn nếu TooltipButton không forwardRef chuẩn */}
      <span style={{ display: "inline-block" }}>
        <TooltipButton tooltip={tooltip} {...buttonProps} />
      </span>
    </Popconfirm>
  );
};
