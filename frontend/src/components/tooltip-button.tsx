import React from "react";
import { Button, Tooltip } from "antd";
import type { ButtonProps, TooltipProps } from "antd";

interface TooltipButtonProps extends ButtonProps {
  tooltip: React.ReactNode;
  tooltipPlacement?: TooltipProps["placement"];
}

export const TooltipButton: React.FC<TooltipButtonProps> = ({
  tooltip,
  tooltipPlacement = "top",
  children,
  style,
  className,
  ...buttonProps
}) => {
  const buttonNode = (
    <Button
      style={buttonProps.disabled ? { pointerEvents: "none" } : undefined}
      {...buttonProps}>
      {children}
    </Button>
  );

  return (
    <Tooltip title={tooltip} placement={tooltipPlacement}>
      {/* Nếu disabled, bọc trong span để Tooltip bắt được sự kiện hover.
         Gán style/className vào wrapper để giữ đúng layout.
      */}
      {buttonProps.disabled ? (
        <span
          style={{ display: "inline-block", cursor: "not-allowed", ...style }}
          className={className}>
          {buttonNode}
        </span>
      ) : (
        // Nếu không disabled, render button trực tiếp (kèm style gốc)
        <span style={style} className={className}>
          <Button {...buttonProps}>{children}</Button>
        </span>
      )}
    </Tooltip>
  );
};
