import { getProductLotByCode } from "@/features/factory/lot/actions";
import { useQuery } from "@tanstack/react-query";
import { Badge, Form, Input, InputNumber, Spin, Table, Typography } from "antd";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface ProductLotItemDetailsProps {
  productLotCode?: string;
  parentFieldName: number;
  form: any;
}

const ProductLotItemDetails = ({
  productLotCode,
  parentFieldName,
  form,
}: ProductLotItemDetailsProps) => {
  const ts = useTranslations("Sales");
  const tc = useTranslations("Common");
  const { data: lotDetail, isLoading } = useQuery({
    queryKey: ["product-lot-detail", productLotCode],
    queryFn: () => getProductLotByCode(String(productLotCode)),
    enabled: !!productLotCode,
  });

  if (!productLotCode || isLoading || !lotDetail?.data) {
    return isLoading ? (
      <div className="py-0.5 text-center">
        <Spin size="small" />
      </div>
    ) : null;
  }

  const handlePriceChange = (
    changedValue: number | null,
    changedIndex: number,
  ) => {
    const items = lotDetail?.data?.items || [];
    // Lấy mảng lot_items hiện tại từ form
    const formLotItems =
      form.getFieldValue(["items", parentFieldName, "lot_items"]) || [];

    let total = 0;
    items.forEach((item: any, idx: number) => {
      // Lấy giá trị mới nhất của field đang sửa, hoặc lấy giá trị cũ từ form
      const itemPrice =
        idx === changedIndex
          ? changedValue || 0
          : formLotItems[idx]?.price || 0;
      total += Number(itemPrice) || 0;
    });

    // Cập nhật giá trị tổng tiền ra field bên ngoài OrderForm
    form.setFieldValue(["items", parentFieldName, "price"], total);
  };

  const columns = [
    {
      title: tc("index"),
      dataIndex: "idx",
      key: "idx",
      width: 40,
      render: (_: any, __: any, index: number) => (
        <Text className="text-[10px] text-gray-400">{index + 1}</Text>
      ),
    },
    {
      title: ts("lot_code"),
      dataIndex: "rubber_block_code",
      key: "rubber_block_code",
      render: (text: string) => (
        <Text strong className="text-[10px] uppercase font-mono">
          {text}
        </Text>
      ),
    },
    {
      title: ts("price"),
      dataIndex: "price",
      key: "price",
      width: 130,
      render: (_: any, record: any, index: number) => (
        <>
          <Form.Item
            name={[parentFieldName, "lot_items", index, "product_lot_item_id"]}
            initialValue={record.product_lot_item_id}
            hidden>
            <Input />
          </Form.Item>
          <Form.Item
            name={[parentFieldName, "lot_items", index, "price"]}
            rules={[{ required: true, message: ts("enter_price") }]}
            noStyle>
            <InputNumber
              size="small"
              placeholder={ts("enter_price")}
              min={0}
              style={{ width: "100%" }}
              formatter={(value) =>
                `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ",")
              }
              parser={(value) =>
                (value ? String(value).replace(/,/g, "") : "") as any
              }
              onChange={(val) => handlePriceChange(val, index)}
            />
          </Form.Item>
        </>
      ),
    },
    {
      title: ts("product_type"),
      dataIndex: "product_type_name",
      key: "product_type_name",
    },
    {
      title: ts("weight"),
      dataIndex: "weight_snapshot",
      key: "weight_snapshot",
      width: 80,
      align: "right" as const,
      render: (text: number) => <Text className="text-[10px]">{text} kg</Text>,
    },
  ];

  return (
    <div className="mt-0.5 p-1 bg-gray-50/30 rounded border border-gray-100">
      <div className="border-t border-gray-100/50 pt-0.5">
        <Table
          dataSource={lotDetail?.data?.items}
          columns={columns}
          size="small"
          pagination={false}
          rowKey="product_lot_item_id"
          scroll={{ y: 150 }}
          className="compact-table"
          rowClassName="compact-row"
        />
        <style
          dangerouslySetInnerHTML={{
            __html: `
          .compact-table .ant-table-thead > tr > th {
            padding: 2px 8px !important;
            font-size: 9px !important;
            background: #f8fafc !important;
            height: 24px !important;
            text-transform: uppercase;
            color: #94a3b8;
          }
          .compact-table .ant-table-tbody > tr > td {
            padding: 2px 8px !important;
            height: 24px !important;
            border-bottom: 1px solid #f1f5f9;
          }
          .compact-table .ant-table-body {
            scrollbar-width: thin;
          }
        `,
          }}
        />
      </div>
    </div>
  );
};

export default ProductLotItemDetails;
