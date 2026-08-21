"use client";

import {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  useMemo,
} from "react";
import { CartItemConfig, CourseConfig } from "@/lib/types";

import { getListCoursesApi } from "@/lib/api";
import { formatCourse } from "@/lib/utils";

// Constants
const CART_STORAGE_KEY = "thien-dieu-cart";

interface CartContextValue {
  cartItems: CartItemConfig[];
  addToCart: (product: CourseConfig) => void;
  removeFromCart: (productId: number) => void;
  updateCartItemQuantity: (productId: number, quantity: number) => void;
  clearCart: () => void;
  cartTotal: number;
  cartCount: number;
  data: CourseConfig[];
  isCartShow: boolean;
  setIsCartShow: React.Dispatch<React.SetStateAction<boolean>>;
  isLoading: boolean;
}

const CartContext = createContext<CartContextValue>({
  cartItems: [],
  addToCart: () => {},
  removeFromCart: () => {},
  updateCartItemQuantity: () => {},
  clearCart: () => {},
  cartTotal: 0,
  cartCount: 0,
  data: [],
  isCartShow: false,
  setIsCartShow: () => {},
  isLoading: false,
});

export const useCart = () => {
  return useContext(CartContext);
};

interface Props {
  children: React.ReactNode;
}

export const CartProvider = ({ children }: Props) => {
  const [cartItems, setCartItems] = useState<CartItemConfig[]>([]);
  const [data, setData] = useState<CourseConfig[]>([]);
  const [isCartShow, setIsCartShow] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  // Load cart from localStorage when component mounts
  useEffect(() => {
    const loadCartFromStorage = () => {
      try {
        const storedCart = localStorage.getItem(CART_STORAGE_KEY);
        if (storedCart) {
          setCartItems(JSON.parse(storedCart));
        }
      } catch (error) {
        console.error("Failed to load cart from localStorage:", error);
      } finally {
        setIsLoading(false);
      }
    };

    loadCartFromStorage();
  }, []);

  // Save cart to localStorage whenever it changes
  useEffect(() => {
    if (!isLoading) {
      try {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cartItems));
      } catch (error) {
        console.error("Failed to save cart to localStorage:", error);
      }
    }
  }, [cartItems, isLoading]);

  // Fetch product data
  /* useEffect(() => {
    const fetchProductData = async () => {
      try {
        const response = await getListCoursesApi({});
        if (response.result != "success") {
          throw new Error(response.errorMessage);
        }
        const allCourses = response.data.records.map(
          formatCourse
        ) as CourseConfig[];
        setData(allCourses);
      } catch (error) {
        console.error("Failed to fetch product data:", error);
      }
    };

    fetchProductData();
  }, []); */

  const addToCart = useCallback((product: CourseConfig) => {
    setCartItems((prevCartItems) => {
      const existingCartItemIndex = prevCartItems.findIndex(
        (item) => item.product.courseId === product.courseId
      );

      if (existingCartItemIndex !== -1) {
        const updatedCartItems = [...prevCartItems];
        updatedCartItems[existingCartItemIndex] = {
          ...updatedCartItems[existingCartItemIndex],
          quantity: updatedCartItems[existingCartItemIndex].quantity + 1,
        };
        return updatedCartItems;
      } else {
        return [...prevCartItems, { product, quantity: 1 }];
      }
    });
  }, []);

  const removeFromCart = useCallback((productId: number) => {
    setCartItems((prevCartItems) =>
      prevCartItems.filter((item) => item.product.courseId !== productId)
    );
  }, []);

  const updateCartItemQuantity = useCallback(
    (productId: number, quantity: number) => {
      if (quantity <= 0) {
        removeFromCart(productId);
        return;
      }

      setCartItems((prevCartItems) => {
        const existingCartItemIndex = prevCartItems.findIndex(
          (item) => item.product.courseId === productId
        );

        if (existingCartItemIndex !== -1) {
          const updatedCartItems = [...prevCartItems];
          updatedCartItems[existingCartItemIndex] = {
            ...updatedCartItems[existingCartItemIndex],
            quantity,
          };
          return updatedCartItems;
        }

        return prevCartItems;
      });
    },
    [removeFromCart]
  );

  const clearCart = useCallback(() => {
    setCartItems([]);
  }, []);

  // Memoize derived state
  const cartTotal = useMemo(
    () =>
      cartItems.reduce(
        (total, item) => total + item.product.price * item.quantity,
        0
      ),
    [cartItems]
  );

  const cartCount = useMemo(
    () => cartItems.reduce((count, item) => count + item.quantity, 0),
    [cartItems]
  );

  const contextValue = useMemo(
    () => ({
      cartItems,
      addToCart,
      removeFromCart,
      updateCartItemQuantity,
      clearCart,
      cartTotal,
      cartCount,
      data,
      isCartShow,
      setIsCartShow,
      isLoading,
    }),
    [
      cartItems,
      addToCart,
      removeFromCart,
      updateCartItemQuantity,
      clearCart,
      cartTotal,
      cartCount,
      data,
      isCartShow,
      setIsCartShow,
      isLoading,
    ]
  );

  return (
    <CartContext.Provider value={contextValue}>{children}</CartContext.Provider>
  );
};
