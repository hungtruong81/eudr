import { DeleteOutlined } from "@ant-design/icons";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Descriptions,
  Modal,
  Table,
  Tag,
  Space,
  message,
  Spin,
  Typography,
} from "antd";
import React from "react";
import { getPalletItems, deletePalletItems } from "../actions";
import { IPallet, IPalletItem } from "../types";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";
import { usePermissions } from "@/contexts/permission-context";

interface PalletDetailModalProps {
  open: boolean;
  onClose: () => void;
  pallet: IPallet | null;
  factories: any[] | undefined;
}

const PalletDetailModal = ({
  open,
  onClose,
  pallet,
  factories,
}: PalletDetailModalProps) => {
  const t = useTranslations("Pallet");
  const tc = useTranslations("Common");
  const queryClient = useQueryClient();
  const { trader } = usePermissions();

  const factoryMap = React.useMemo(() => {
    const map = new Map<number, string>();
    factories?.forEach((f) => {
      map.set(f.factory_id, f.factory_name);
    });
    return map;
  }, [factories]);

  // Fetch pallet items using react-query
  const { data, isLoading } = useQuery({
    queryKey: ["pallet-items", pallet?.pallet_code],
    queryFn: () =>
      getPalletItems({
        pallet_code: pallet?.pallet_code || "",
        page: 1,
        limit: 100,
      }),
    enabled: !!pallet?.pallet_code && open,
  });

  const palletData = data?.data?.pallet || pallet;
  const items = data?.data?.items || [];

  // Delete pallet item mutation
  const deleteItemMutation = useMutation({
    mutationFn: ({
      palletCode,
      itemId,
    }: {
      palletCode: string;
      itemId: number;
    }) => deletePalletItems(palletCode, itemId),
    onSuccess: () => {
      message.success(t("delete_item_success"));
      // Invalidate the pallet items query to refresh the list
      queryClient.invalidateQueries({
        queryKey: ["pallet-items", pallet?.pallet_code],
      });
      // Also invalidate the main pallet list query to update bale counts/weights
      queryClient.invalidateQueries({ queryKey: ["pallet"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleDeleteItem = (itemId: number) => {
    if (pallet?.pallet_code) {
      deleteItemMutation.mutate({
        palletCode: pallet.pallet_code,
        itemId,
      });
    }
  };

  const getPalletStatusTag = (status: string) => {
    const text = t(`status_${status}`) || status;
    let color = "default";
    switch (status) {
      case "draft":
        color = "blue";
        break;
      case "packed":
        color = "green";
        break;
      case "shipped":
        color = "purple";
        break;
      case "cancelled":
        color = "red";
        break;
    }
    return <Tag color={color}>{text}</Tag>;
  };

  const columns = [
    {
      title: t("block_code"),
      dataIndex: "rubber_block_code",
      key: "rubber_block_code",
      render: (val: string) => (
        <span className="font-bold uppercase">{val}</span>
      ),
    },
    {
      title: t("weight"),
      dataIndex: "weight",
      key: "weight",
      align: "right" as const,
      render: (val: any) =>
        val ? `${Number(val).toLocaleString("vi-VN")} kg` : "-",
    },
    {
      title: t("grade"),
      dataIndex: "grade",
      key: "grade",
      render: (val: string) => <Tag color="geekblue">{val || "-"}</Tag>,
    },
    {
      title: t("status"),
      dataIndex: "status",
      key: "status",
      render: (status: string) => {
        let color = "default";
        let text = status;
        if (status === "available") {
          color = "green";
          text = "Có sẵn";
        } else if (status === "allocated") {
          color = "orange";
          text = "Đã phân bổ";
        } else if (status === "shipped") {
          color = "purple";
          text = "Đã giao";
        }
        return <Tag color={color}>{text}</Tag>;
      },
    },
    {
      title: t("added_at"),
      dataIndex: "added_at",
      key: "added_at",
      render: (val: string) =>
        val ? new Date(val).toLocaleString("vi-VN") : "-",
    },
    {
      title: tc("actions"),
      key: "actions",
      align: "center" as const,
      render: (_: any, record: IPalletItem) => {
        return (
          <ConfirmTooltipButton
            confirmTitle={t("confirm_delete_item_title")}
            confirmDescription={t("confirm_delete_item_desc", {
              code: record.rubber_block_code.toUpperCase(),
            })}
            tooltip={tc("delete")}
            danger
            icon={<DeleteOutlined />}
            onConfirm={() => handleDeleteItem(record.pallet_item_id)}
            loading={
              deleteItemMutation.isPending &&
              deleteItemMutation.variables?.itemId === record.pallet_item_id
            }
          />
        );
      },
    },
  ].filter((col) => {
    if (col.key === "actions") {
      return (
        trader.pallet.pack.self ||
        trader.pallet.pack.own ||
        trader.pallet.full
      );
    }
    return true;
  });

  const detailColumns = [
    {
      title: tc("info") || "Info",
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => (
        <Typography.Text type="secondary">{text}</Typography.Text>
      ),
    },
    {
      title: tc("detail") || "Detail",
      dataIndex: "value",
      key: "value",
      render: (text: any) => (
        <Typography.Text strong>{text ?? "—"}</Typography.Text>
      ),
    },
  ];

  return (
    <Modal
      open={open}
      onCancel={onClose}
      footer={null}
      title={t("detail_title")}
      width={900}>
      {palletData ? (
        <Space orientation="vertical" style={{ width: "100%" }} size="large">
          <Table
            dataSource={[
              {
                label: t("pallet_code"),
                value: (
                  <span className="font-bold uppercase">
                    {palletData.pallet_code}
                  </span>
                ),
              },
              {
                label: t("warehouse"),
                value:
                  factoryMap.get(Number(palletData.warehouse_id)) ||
                  `#${palletData.warehouse_id}`,
              },
              {
                label: t("status"),
                value: getPalletStatusTag(palletData.status),
              },
              {
                label: t("total_bales"),
                value: palletData.total_bales ?? 0,
              },
              {
                label: t("total_weight"),
                value: palletData.total_weight
                  ? `${Number(palletData.total_weight).toLocaleString("vi-VN")} kg`
                  : "0 kg",
              },
              {
                label: t("created_at"),
                value: palletData.created_at
                  ? new Date(palletData.created_at).toLocaleString("vi-VN")
                  : "-",
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
            showHeader={false}
            style={{ marginBottom: 16 }}
          />

          <div>
            <h3 className="text-base font-semibold mb-3">{t("blocks_list")}</h3>
            <Spin spinning={isLoading}>
              <Table
                dataSource={items}
                columns={columns}
                rowKey="pallet_item_id"
                pagination={false}
                bordered
                size="small"
              />
            </Spin>
          </div>
        </Space>
      ) : (
        <Spin />
      )}
    </Modal>
  );
};

export default PalletDetailModal;
