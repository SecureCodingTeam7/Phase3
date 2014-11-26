package scs;

import org.apache.commons.net.time.TimeUDPClient;

import java.io.IOException;
import java.net.InetAddress;

public class NetworkTime {
	
	private static final String HOST = "time-a.nist.gov";
	
	public static long getTime() throws IOException {
		TimeUDPClient client = new TimeUDPClient();
		try {
	        client.setDefaultTimeout(60000);
	        client.open();
	        return client.getTime(InetAddress.getByName(HOST)) - TimeUDPClient.SECONDS_1900_TO_1970;
		} finally {
            client.close();
        }
	}
}
