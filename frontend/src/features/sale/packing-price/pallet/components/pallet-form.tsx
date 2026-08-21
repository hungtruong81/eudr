import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import BaseSheet from "@/components/shared/base-sheet";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { getFgReceiptSummary } from "@/features/factory/fg-receipt-summary/actions";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import { IFgReceiptSummary } from "@/features/factory/fg-receipt-summary/types";
import { useQuery } from "@tanstack/react-query";
import { Col, Form, Input, Row, message } from "antd";
import React, { useEffect, useState } from "react";
import { generatePalletCode, getPalletItems } from "../actions";
import { IPallet, IPalletData } from "../types";
import { useTranslations } from "next-intl";

interface PalletFormProps {
  open: boolean;
  onClose: () => void;
  record: IPallet | null;
  onFinish: (values: IPalletData) => Promise<void>;
  loading?: boolean;
}

const PalletForm = ({
  open,
  onClose,
  record,
  onFinish,
  loading,
}: PalletFormProps) => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const [form] = Form.useForm();
  const [generatedCode, setGeneratedCode] = useState<string>("");

  // Fetch pallet items if editing
  const { data: palletItemsData } = useQuery({
    queryKey: ["pallet-items", record?.pallet_code],
    queryFn: () =>
      getPalletItems({
        pallet_code: record?.pallet_code || "",
        page: 1,
        limit: 100,
      }),
    enabled: !!record?.pallet_code && open,
  });

  const items = React.useMemo(
    () => palletItemsData?.data?.items || [],
    [palletItemsData]
  );

  const initialRubberBlockOptions = React.useMemo(() => {
    return items.map((item) => ({
      label: `${item.rubber_block_code?.toUpperCase()} (${item.weight} kg - ${item.grade})`,
      value: String(item.rubber_block_id),
    }));
  }, [items]);

  useEffect(() => {
    if (open) {
      if (record) {
        const itemIds = items.map((item) => String(item.rubber_block_id));
        form.setFieldsValue({
          pallet_code: record.pallet_code,
          warehouse_id: String(record.warehouse_id),
          rubber_block_ids: itemIds,
        });
      } else {
        form.resetFields();
        fetchCode();
      }
    }
  }, [open, record, form, items]);

  const fetchCode = async () => {
    try {
      const res = await generatePalletCode();
      if (res?.data?.pallet_code) {
        setGeneratedCode(res.data.pallet_code);
        form.setFieldValue("pallet_code", res.data.pallet_code);
      }
    } catch (error) {
      console.error("Error generating pallet code:", error);
    }
  };

  const handleFinish = async (values: any) => {
    const formattedValues: IPalletData = {
      pallet_code: values.pallet_code,
      // warehouse_id: Number(values.warehouse_id),
      warehouse_id: 1,
      rubber_block_ids: values.rubber_block_ids
        ? values.rubber_block_ids.map(Number)
        : [],
    };
    await onFinish(formattedValues);
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("create_title")}
      loading={loading}
      width={600}>
      <Form form={form} layout="vertical" onFinish={handleFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="pallet_code"
              label={t("pallet_code")}
              rules={[{ required: true }]}>
              <Input
                placeholder={t("pallet_code")}
                className="uppercase font-bold"
                disabled
              />
            </Form.Item>
          </Col>
          {/* <Col span={24}>
            <Form.Item
              name="warehouse_id"
              label={t("warehouse")}
              rules={[
                { required: true, message: t("select_warehouse_error") },
              ]}>
              <InfiniteScrollSelect<IFactory>
                queryKey={["factories-pallet"]}
                fetchFn={getFactory}
                mapOption={(item) => ({
                  label: item.factory_name,
                  value: String(item.factory_id),
                })}
                placeholder={t("select_warehouse")}
                initialOptions={
                  record?.warehouse_id
                    ? [
                        {
                          label: `Warehouse #${record.warehouse_id}`,
                          value: String(record.warehouse_id),
                        },
                      ]
                    : []
                }
              />
            </Form.Item>
          </Col> */}
          <Col span={24}>
            <Form.Item name="rubber_block_ids" label={t("select_blocks")}>
              <InfiniteScrollSelect<IFgReceiptSummary>
                queryKey={["available-rubber-blocks"]}
                fetchFn={(params) =>
                  getFgReceiptSummary({ ...params, status: "available" })
                }
                mapOption={(item) => ({
                  label: `${item.rubber_block_code?.toUpperCase()} (${item.weight} kg - ${item.grade})`,
                  value: String(item.rubber_block_id),
                })}
                placeholder={t("select_blocks_placeholder")}
                mode="multiple"
                maxCount={999}
                allowClear
                initialOptions={initialRubberBlockOptions}
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};

export default PalletForm;
