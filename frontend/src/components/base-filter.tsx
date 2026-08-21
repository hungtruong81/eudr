import { RotateLeftOutlined } from "@ant-design/icons";
import { Form, FormInstance, Space } from "antd";
import { FormProps } from "antd/lib";
import { debounce } from "lodash";
import React, { ReactNode, useCallback, useEffect } from "react";
import { TooltipButton } from "./tooltip-button";

interface BaseFilterProps {
  onFinish: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
  resetTooltip?: string;
  children: ReactNode;
  form?: FormInstance<any>;
  props?: FormProps;
  debounceTime?: number;
}

export const BaseFilter: React.FC<BaseFilterProps> = ({
  onFinish,
  onReset,
  loading,
  resetTooltip = "Đặt lại",
  children,
  form,
  props,
  debounceTime = 300,
}) => {
  const [defaultForm] = Form.useForm();
  const formInstance = form || defaultForm;

  // eslint-disable-next-line react-hooks/exhaustive-deps
  const debouncedFinish = useCallback(
    debounce((values: any) => {
      onFinish(values);
    }, debounceTime),
    [onFinish, debounceTime],
  );

  useEffect(() => {
    return () => {
      debouncedFinish.cancel();
    };
  }, [debouncedFinish]);

  const handleReset = () => {
    formInstance.resetFields();
    onReset();
  };

  const handleValuesChange = (changedValues: any, allValues: any) => {
    debouncedFinish(allValues);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Form
        form={formInstance}
        onFinish={onFinish}
        onValuesChange={handleValuesChange}
        layout="inline"
        className="gap-y-4"
        {...props}>
        {children}
        <Form.Item className="mb-0">
          <Space>
            <TooltipButton
              tooltip={resetTooltip}
              onClick={handleReset}
              icon={<RotateLeftOutlined size={16} />}
              disabled={loading}
            />
          </Space>
        </Form.Item>
      </Form>
    </Space>
  );
};
