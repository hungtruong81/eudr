"use client";
import React, { useState } from "react";
import dayjs from "dayjs";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, Row, Col, Spin, Empty, Pagination, message } from "antd";
import { PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import {
  getFinishedGoodsReceipt,
  deleteFinishedGoodsReceipt,
  IGetFinishedGoodsReceiptParams,
} from "../actions";
import { IFinishedGoodsReceipt } from "../types";
import FinishedGoodsReceiptFilter from "./finished-goods-receipt-filter";
import FinishedGoodsReceiptCard from "./finished-goods-receipt-card";
import FinishedGoodsReceiptForm from "./finished-goods-receipt-form";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

const FinishedGoodsReceipt = () => {
  const t = useTranslations("Factory.fg_receipt");
  const tc = useTranslations("Common");

  const queryClient = useQueryClient();
  const [params, setParams] = useState<Partial<IGetFinishedGoodsReceiptParams>>(
    {
      page: 1,
      limit: 12,
      created_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      created_date_to: dayjs().format("YYYY-MM-DD"),
    },
  );

  const [openForm, setOpenForm] = useState(false);
  const [selectedRecord, setSelectedRecord] =
    useState<IFinishedGoodsReceipt | null>(null);

  const { data, isFetching, refetch } = useQuery({
    queryKey: ["finished-goods-receipt", params],
    queryFn: () =>
      getFinishedGoodsReceipt(params as IGetFinishedGoodsReceiptParams),
  });

  const deleteMutation = useMutation({
    mutationFn: (code: string) => deleteFinishedGoodsReceipt(code),
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["finished-goods-receipt"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleSearch = (newParams: Partial<IGetFinishedGoodsReceiptParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handleEdit = (record: IFinishedGoodsReceipt) => {
    setSelectedRecord(record);
    setOpenForm(true);
  };

  const handleAdd = () => {
    setSelectedRecord(null);
    setOpenForm(true);
  };

  const handleDelete = (record: IFinishedGoodsReceipt) => {
    deleteMutation.mutate(record.finished_goods_receipt_code);
  };

  const handleView = (record: IFinishedGoodsReceipt) => {
    setSelectedRecord(record);
    setOpenForm(true);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex align="center" justify="space-between">
        <FinishedGoodsReceiptFilter onSearch={handleSearch} />

        <TooltipButton
          tooltip={t("add_title")}
          icon={<PlusOutlined />}
          type="primary"
          onClick={handleAdd}>
          {tc("add")}
        </TooltipButton>
      </Flex>

      <Spin spinning={isFetching}>
        {(!data?.data?.records || data.data.records.length === 0) &&
        !isFetching ? (
          <Empty description={t("empty_description")} />
        ) : (
          <Flex vertical gap="large">
            <Row gutter={[16, 16]}>
              {data?.data?.records?.map((record) => (
                <Col
                  xs={24}
                  sm={24}
                  md={12}
                  lg={8}
                  xl={6}
                  key={record.finished_goods_receipt_id}>
                  <FinishedGoodsReceiptCard
                    record={record}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    onView={handleView}
                    deleting={
                      deleteMutation.isPending &&
                      deleteMutation.variables ===
                        record.finished_goods_receipt_code
                    }
                  />
                </Col>
              ))}
            </Row>

            {data && data.data.total_records > 0 && (
              <Flex justify="flex-end">
                <Pagination
                  current={+data.data.current_page}
                  total={+data.data.total_records}
                  pageSize={+data.data.page_limit}
                  showSizeChanger
                  onChange={(page, limit) => {
                    setParams((prev) => ({
                      ...prev,
                      page,
                      limit,
                    }));
                  }}
                />
              </Flex>
            )}
          </Flex>
        )}
      </Spin>

      <FinishedGoodsReceiptForm
        open={openForm}
        onClose={() => setOpenForm(false)}
        record={selectedRecord}
        onSuccess={refetch}
      />
    </Space>
  );
};

export default FinishedGoodsReceipt;
