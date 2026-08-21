import { Icons } from "@/components/icons";
import { FaTwitter } from "react-icons/fa";
import { FaYoutube } from "react-icons/fa6";
import { RiInstagramFill } from "react-icons/ri";

export const BLUR_FADE_DELAY = 0.15;

export const siteConfig = {
  name: "EUDR Admin",
  description: "",
  url: process.env.NEXT_PUBLIC_APP_URL || "http://localhost:3000",
  keywords: [""],
  links: {
    email: "abc@gmail.com",
    phone: "090811111",
    web: "https://eudr-2025-demo.com",
    facebook: "#",
  },
  header: [
    {
      href: "/",
      label: "Trang chủ",
    },
    {
      trigger: "Application",
      content: {
        main: {
          icon: <Icons.logoThienDieu className="w-full" />,
          title: "Hello A",
          description: "",
          href: "#",
        },
        items: [
          {
            href: "#",
            title: "Example 1",
            description: "",
            },
          {
            href: "#",
            title: "Example 2",
            description: "",
          },

        ],
      },
    },
    {
      href: "/about-us",
      label: "About Us",
    },
  ],
  pricing: [
    {
      name: "BASIC",
      href: "#",
      price: "$19",
      period: "month",
      yearlyPrice: "$16",
      features: [
        "1 User",
        "5GB Storage",
        "Basic Support",
        "Limited API Access",
        "Standard Analytics",
      ],
      description: "Perfect for individuals and small projects",
      buttonText: "Subscribe",
      isPopular: false,
    },
    {
      name: "PRO",
      href: "#",
      price: "$49",
      period: "month",
      yearlyPrice: "$40",
      features: [
        "5 Users",
        "50GB Storage",
        "Priority Support",
        "Full API Access",
        "Advanced Analytics",
      ],
      description: "Ideal for growing businesses and teams",
      buttonText: "Subscribe",
      isPopular: true,
    },
    {
      name: "ENTERPRISE",
      href: "#",
      price: "$99",
      period: "month",
      yearlyPrice: "$82",
      features: [
        "Unlimited Users",
        "500GB Storage",
        "24/7 Premium Support",
        "Custom Integrations",
        "AI-Powered Insights",
      ],
      description: "For large-scale operations and high-volume users",
      buttonText: "Subscribe",
      isPopular: false,
    },
  ],
  faqs: [
    {
      question: "Thiên Điểu Art dạy vẽ trên chất liệu gì?",
      answer: (
        <span>
          Thiên Điểu Art chuyên dạy vẽ trên áo dài truyền thống, vải lụa, và các loại vải chuyên dùng cho trang phục nghệ thuật. Học viên sẽ được làm quen với màu vẽ chuyên dụng, kỹ thuật xử lý vải và cách tạo bố cục hài hòa cho từng thiết kế.
        </span>
      ),
    },
    {
      question: "Tôi chưa biết vẽ, có thể tham gia lớp học không?",
      answer: (
        <span>
          Hoàn toàn có thể! Các khóa học tại Thiên Điểu Art được thiết kế phù hợp cho người mới bắt đầu, hướng dẫn từ cơ bản đến nâng cao. Bạn không cần có nền tảng mỹ thuật trước đó – chỉ cần đam mê và sự kiên nhẫn!
        </span>
      ),
    },
    {
      question: "Lớp học online có hiệu quả như học trực tiếp không?",
      answer: (
        <span>
          Có. Lớp học online của Thiên Điểu Art có giáo trình chi tiết, video hướng dẫn trực quan, và giảng viên theo sát hỗ trợ qua các buổi feedback định kỳ. Học viên vẫn có thể đạt kết quả tương đương lớp trực tiếp nếu nghiêm túc thực hành theo lộ trình.
        </span>
      ),
    },
    {
      question: "Tôi cần chuẩn bị gì khi học vẽ online?",
      answer: (
        <span>
          Bạn cần chuẩn bị các dụng cụ cơ bản như: áo dài trắng (hoặc vải lụa), màu vẽ trên vải, cọ vẽ, bảng pha màu, và một không gian học tập yên tĩnh. Thiên Điểu Art sẽ gửi danh sách dụng cụ chi tiết và tư vấn nơi mua uy tín trước khi lớp học bắt đầu.
        </span>
      ),
    },
    {
      question: "Sau khóa học, tôi có thể tự vẽ áo dài để kinh doanh không?",
      answer: (
        <span>
          Có! Sau khóa học, bạn sẽ nắm vững kỹ năng vẽ trên vải, cách chọn bố cục, phối màu và hoàn thiện sản phẩm. Nhiều học viên của Thiên Điểu Art đã bắt đầu nhận đơn đặt hàng, mở shop nhỏ hoặc kết hợp nghệ thuật vẽ với kinh doanh áo dài.
        </span>
      ),
    },
  ],
  footer: [
    {
      title: "Product",
      links: [
        { href: "#", text: "Features", icon: null },
        { href: "#", text: "Pricing", icon: null },
        { href: "#", text: "Documentation", icon: null },
        { href: "#", text: "API", icon: null },
      ],
    },
    {
      title: "Company",
      links: [
        { href: "#", text: "About Us", icon: null },
        { href: "#", text: "Careers", icon: null },
        { href: "#", text: "Blog", icon: null },
        { href: "#", text: "Press", icon: null },
        { href: "#", text: "Partners", icon: null },
      ],
    },
    {
      title: "Resources",
      links: [
        { href: "#", text: "Community", icon: null },
        { href: "#", text: "Contact", icon: null },
        { href: "#", text: "Support", icon: null },
        { href: "#", text: "Status", icon: null },
      ],
    },
    {
      title: "Social",
      links: [
        {
          href: "#",
          text: "Twitter",
          icon: <FaTwitter />,
        },
        {
          href: "#",
          text: "Instagram",
          icon: <RiInstagramFill />,
        },
        {
          href: "#",
          text: "Youtube",
          icon: <FaYoutube />,
        },
      ],
    },
  ],
};

export type SiteConfig = typeof siteConfig;
