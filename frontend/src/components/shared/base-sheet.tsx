import { Button, Drawer, Space } from "antd";
import { DrawerProps } from "antd/lib";
import React, { ReactNode } from "react";

interface BaseSheetProps {
  open: boolean;
  onClose: () => void;
  onOk?: () => void;
  title: ReactNode;
  width?: string | number;
  loading?: boolean;
  okText?: string;
  cancelText?: string;
  extra?: ReactNode;
  children: ReactNode;
  props?: DrawerProps;
}

const BaseSheet: React.FC<BaseSheetProps> = ({
  open,
  onClose,
  onOk,
  title,
  width = "80%",
  loading = false,
  okText = "Lưu",
  cancelText = "Hủy",
  extra,
  children,
  ...props
}) => {
  const defaultExtra = (
    <Space>
      <Button onClick={onClose}>{cancelText}</Button>
      <Button type="primary" onClick={onOk} loading={loading}>
        {okText}
      </Button>
    </Space>
  );

  return (
    <Drawer
      title={title}
      size={width}
      onClose={onClose}
      open={open}
      extra={extra || (onOk ? defaultExtra : null)}
      {...props}>
      {children}
    </Drawer>
  );
};

export default BaseSheet;
