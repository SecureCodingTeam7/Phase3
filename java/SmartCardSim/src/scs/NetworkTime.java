package scs;

import java.io.IOException;
import java.net.InetAddress;
import java.net.UnknownHostException;

import org.apache.commons.net.time.TimeUDPClient;

public class NetworkTime {
	
	private static final String HOST = "time-a.nist.gov";
	
	public static long getTime() throws IOException {
		TimeUDPClient client = new TimeUDPClient();
		try {
	        client.setDefaultTimeout(60000);
	        client.open();
	        return client.getTime(InetAddress.getByName(HOST));
		} finally {
            client.close();
        }
	}
}
